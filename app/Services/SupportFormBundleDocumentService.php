<?php

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;
use ZipArchive;

class SupportFormBundleDocumentService
{
    private const W = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    private const R = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const PACKAGE_R = 'http://schemas.openxmlformats.org/package/2006/relationships';

    private const CONTENT_TYPES = 'http://schemas.openxmlformats.org/package/2006/content-types';

    private const HEADER_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/header';

    private const FOOTER_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer';

    private int $partSequence = 0;

    private int $relationshipSequence = 0;

    private array $copiedParts = [];

    /**
     * Merge completed forms into one editable DOCX and put one empty section
     * between forms. OOXML parts are copied instead of re-rendered so tables,
     * fields, images, headers, footnotes and page settings remain intact.
     */
    public function merge(array $documents): string
    {
        $this->partSequence = 0;
        $this->relationshipSequence = 0;
        $this->copiedParts = [];

        if (count($documents) < 2) {
            throw new RuntimeException('Cần ít nhất hai biểu mẫu để tạo bộ hồ sơ.');
        }
        foreach ($documents as $document) {
            if (! is_string($document) || ! is_file($document)) {
                throw new RuntimeException('Một biểu mẫu Word chưa được tạo đầy đủ.');
            }
        }

        $output = tempnam(sys_get_temp_dir(), 'forms_docx_');
        if ($output === false) {
            throw new RuntimeException('Không tạo được tệp Word tạm.');
        }
        if (! copy($documents[0], $output)) {
            unlink($output);
            throw new RuntimeException('Không khởi tạo được bộ hồ sơ Word.');
        }

        $target = new ZipArchive;
        $targetOpened = false;
        try {
            if ($target->open($output) !== true) {
                throw new RuntimeException('Không mở được bộ hồ sơ Word.');
            }
            $targetOpened = true;

            $main = $this->xmlFromZip($target, 'word/document.xml');
            $relationships = $this->xmlFromZip($target, 'word/_rels/document.xml.rels');
            $contentTypes = $this->xmlFromZip($target, '[Content_Types].xml');
            $styles = $this->optionalXmlFromZip($target, 'word/styles.xml');
            $numbering = $this->optionalXmlFromZip($target, 'word/numbering.xml');
            $footnotes = $this->optionalXmlFromZip($target, 'word/footnotes.xml');
            $body = $main->getElementsByTagNameNS(self::W, 'body')->item(0);
            if (! $body instanceof DOMElement) {
                throw new RuntimeException('Tệp Word không có nội dung chính.');
            }
            $nextBookmarkId = $this->highestAttribute($main, 'bookmarkStart', 'id', true) + 1;
            $nextDrawingId = $this->highestDrawingId($main) + 1;
            $nextContentControlId = $this->highestContentControlId($main) + 1;
            while ($body->firstChild) {
                $body->removeChild($body->firstChild);
            }

            $this->relationshipSequence = $this->highestRelationshipNumber($relationships) + 1;
            $nextAbstractId = $this->highestAttribute($numbering, 'abstractNum', 'abstractNumId') + 1;
            $nextNumberId = $this->highestAttribute($numbering, 'num', 'numId') + 1;
            $nextPictureBulletId = $this->highestAttribute($numbering, 'numPicBullet', 'numPicBulletId') + 1;
            $nextFootnoteId = $this->highestAttribute($footnotes, 'footnote', 'id', true) + 1;
            [$blankHeaderId, $blankFooterId] = $this->addBlankHeaderAndFooter($target, $relationships, $contentTypes);

            foreach (array_values($documents) as $index => $path) {
                $source = new ZipArchive;
                if ($source->open($path) !== true) {
                    throw new RuntimeException('Không đọc được một biểu mẫu Word.');
                }

                try {
                    $sourceMain = $this->xmlFromZip($source, 'word/document.xml');
                    $sourceRelationships = $this->xmlFromZip($source, 'word/_rels/document.xml.rels');
                    $sourceContentTypes = $this->xmlFromZip($source, '[Content_Types].xml');
                    $sourceBody = $sourceMain->getElementsByTagNameNS(self::W, 'body')->item(0);
                    if (! $sourceBody instanceof DOMElement) {
                        throw new RuntimeException('Một biểu mẫu không có nội dung chính.');
                    }

                    $numberMap = $index === 0 ? [] : $this->mergeNumbering(
                        $source,
                        $numbering,
                        $nextAbstractId,
                        $nextNumberId,
                        $nextPictureBulletId
                    );
                    $defaultStyles = [];
                    $styleMap = $index === 0 ? [] : $this->mergeStyles(
                        $source,
                        $styles,
                        $numberMap,
                        $index,
                        $defaultStyles
                    );
                    $this->applyValueMaps($sourceMain, $styleMap, $numberMap);
                    $this->applyDefaultStyles($sourceMain, $defaultStyles);
                    if ($index > 0) {
                        $this->mergeFootnotes(
                            $source,
                            $footnotes,
                            $sourceMain,
                            $styleMap,
                            $numberMap,
                            $defaultStyles,
                            $nextFootnoteId
                        );
                        $this->copyReferencedParts(
                            $source,
                            $target,
                            $sourceMain,
                            $sourceRelationships,
                            $sourceContentTypes,
                            $relationships,
                            $contentTypes,
                            $styleMap,
                            $numberMap,
                            $index + 1
                        );
                        $this->remapDocumentIds($sourceMain, $nextBookmarkId, $nextDrawingId, $nextContentControlId);
                    }

                    $sectionProperties = $this->finalSectionProperties($sourceBody);
                    $contentNodes = [];
                    foreach ($sourceBody->childNodes as $child) {
                        if ($child instanceof DOMElement && $child->namespaceURI === self::W && $child->localName === 'sectPr') {
                            continue;
                        }
                        $contentNodes[] = $child->cloneNode(true);
                    }
                    foreach ($contentNodes as $child) {
                        $body->appendChild($main->importNode($child, true));
                    }

                    if ($index < count($documents) - 1) {
                        $this->endCurrentSection($main, $body, $sectionProperties);
                        $body->appendChild($this->blankSectionParagraph(
                            $main,
                            $sectionProperties,
                            $blankHeaderId,
                            $blankFooterId
                        ));
                    } else {
                        $body->appendChild($main->importNode($sectionProperties, true));
                    }
                } finally {
                    $source->close();
                }
            }

            $this->writeXml($target, 'word/document.xml', $main);
            $this->writeXml($target, 'word/_rels/document.xml.rels', $relationships);
            $this->writeXml($target, '[Content_Types].xml', $contentTypes);
            if ($styles) {
                $this->writeXml($target, 'word/styles.xml', $styles);
            }
            if ($numbering) {
                $this->writeXml($target, 'word/numbering.xml', $numbering);
            }
            if ($footnotes) {
                $this->writeXml($target, 'word/footnotes.xml', $footnotes);
            }
            $closed = $target->close();
            $targetOpened = false;
            if (! $closed) {
                throw new RuntimeException('Không hoàn tất được bộ hồ sơ Word.');
            }

            return $output;
        } catch (\Throwable $error) {
            if ($targetOpened) {
                $target->close();
            }
            if (is_file($output)) {
                unlink($output);
            }

            throw $error;
        }
    }

    private function mergeNumbering(
        ZipArchive $source,
        ?DOMDocument $base,
        int &$nextAbstractId,
        int &$nextNumberId,
        int &$nextPictureBulletId
    ): array {
        $sourceNumbering = $this->optionalXmlFromZip($source, 'word/numbering.xml');
        if (! $sourceNumbering || ! $base || ! $base->documentElement) {
            return [];
        }

        $abstractMap = [];
        $numberMap = [];
        $pictureMap = [];
        foreach ($sourceNumbering->getElementsByTagNameNS(self::W, 'numPicBullet') as $element) {
            $old = $element->getAttributeNS(self::W, 'numPicBulletId');
            $pictureMap[$old] = (string) $nextPictureBulletId++;
        }
        foreach ($sourceNumbering->getElementsByTagNameNS(self::W, 'abstractNum') as $element) {
            $old = $element->getAttributeNS(self::W, 'abstractNumId');
            $abstractMap[$old] = (string) $nextAbstractId++;
        }
        foreach ($sourceNumbering->getElementsByTagNameNS(self::W, 'num') as $element) {
            $old = $element->getAttributeNS(self::W, 'numId');
            $numberMap[$old] = (string) $nextNumberId++;
        }

        foreach (['numPicBullet', 'abstractNum', 'num'] as $nodeName) {
            foreach ($sourceNumbering->getElementsByTagNameNS(self::W, $nodeName) as $element) {
                $clone = $base->importNode($element, true);
                if ($clone instanceof DOMElement) {
                    $attribute = match ($nodeName) {
                        'numPicBullet' => 'numPicBulletId',
                        'abstractNum' => 'abstractNumId',
                        default => 'numId',
                    };
                    $map = match ($nodeName) {
                        'numPicBullet' => $pictureMap,
                        'abstractNum' => $abstractMap,
                        default => $numberMap,
                    };
                    $old = $clone->getAttributeNS(self::W, $attribute);
                    $clone->setAttributeNS(self::W, 'w:'.$attribute, $map[$old]);
                    $this->replaceValues($clone, 'abstractNumId', $abstractMap);
                    $this->replaceValues($clone, 'numPicBulletId', $pictureMap);
                    $base->documentElement->appendChild($clone);
                }
            }
        }

        return $numberMap;
    }

    private function mergeStyles(
        ZipArchive $source,
        ?DOMDocument $base,
        array $numberMap,
        int $index,
        array &$defaultStyles
    ): array {
        $sourceStyles = $this->optionalXmlFromZip($source, 'word/styles.xml');
        if (! $sourceStyles || ! $base || ! $base->documentElement) {
            return [];
        }

        $styleMap = [];
        foreach ($sourceStyles->getElementsByTagNameNS(self::W, 'style') as $style) {
            $old = $style->getAttributeNS(self::W, 'styleId');
            if ($old !== '') {
                $styleMap[$old] = 'Bundle'.$index.'_'.$old;
                if ($style->getAttributeNS(self::W, 'default') === '1') {
                    $defaultStyles[$style->getAttributeNS(self::W, 'type')] = $styleMap[$old];
                }
            }
        }
        foreach ($sourceStyles->getElementsByTagNameNS(self::W, 'style') as $style) {
            $clone = $base->importNode($style, true);
            if (! $clone instanceof DOMElement) {
                continue;
            }
            $old = $clone->getAttributeNS(self::W, 'styleId');
            if (isset($styleMap[$old])) {
                $clone->setAttributeNS(self::W, 'w:styleId', $styleMap[$old]);
            }
            $clone->removeAttributeNS(self::W, 'default');
            foreach (['basedOn', 'next', 'link', 'pStyle', 'rStyle', 'tblStyle'] as $name) {
                $this->replaceValues($clone, $name, $styleMap);
            }
            $this->replaceValues($clone, 'numId', $numberMap);
            $base->documentElement->appendChild($clone);
        }

        return $styleMap;
    }

    private function mergeFootnotes(
        ZipArchive $source,
        ?DOMDocument $base,
        DOMDocument $sourceMain,
        array $styleMap,
        array $numberMap,
        array $defaultStyles,
        int &$nextFootnoteId
    ): void {
        $sourceFootnotes = $this->optionalXmlFromZip($source, 'word/footnotes.xml');
        if (! $sourceFootnotes || ! $base || ! $base->documentElement) {
            return;
        }

        $idMap = [];
        foreach ($sourceFootnotes->getElementsByTagNameNS(self::W, 'footnote') as $footnote) {
            $old = $footnote->getAttributeNS(self::W, 'id');
            if ((int) $old > 0) {
                $idMap[$old] = (string) $nextFootnoteId++;
            }
        }
        $this->replaceValues($sourceMain, 'footnoteReference', $idMap, 'id');
        $this->applyValueMaps($sourceFootnotes, $styleMap, $numberMap);
        $this->applyDefaultStyles($sourceFootnotes, $defaultStyles);
        foreach ($sourceFootnotes->getElementsByTagNameNS(self::W, 'footnote') as $footnote) {
            $old = $footnote->getAttributeNS(self::W, 'id');
            if (! isset($idMap[$old])) {
                continue;
            }
            $clone = $base->importNode($footnote, true);
            if ($clone instanceof DOMElement) {
                $clone->setAttributeNS(self::W, 'w:id', $idMap[$old]);
                $base->documentElement->appendChild($clone);
            }
        }
    }

    private function copyReferencedParts(
        ZipArchive $source,
        ZipArchive $target,
        DOMDocument $sourceMain,
        DOMDocument $sourceRelationships,
        DOMDocument $sourceContentTypes,
        DOMDocument $baseRelationships,
        DOMDocument $baseContentTypes,
        array $styleMap,
        array $numberMap,
        int $sourceNumber
    ): void {
        $references = [];
        foreach ($sourceMain->getElementsByTagName('*') as $element) {
            foreach ($element->attributes ?? [] as $attribute) {
                if ($attribute->namespaceURI === self::R && in_array($attribute->localName, ['id', 'embed', 'link'], true)) {
                    $references[$attribute->value][] = $attribute;
                }
            }
        }

        foreach ($references as $oldId => $attributes) {
            $sourceRelationship = $this->relationshipById($sourceRelationships, $oldId);
            if (! $sourceRelationship) {
                continue;
            }
            $newId = 'rIdBundle'.$this->relationshipSequence++;
            $relationship = $baseRelationships->createElementNS(self::PACKAGE_R, 'Relationship');
            $relationship->setAttribute('Id', $newId);
            $relationship->setAttribute('Type', $sourceRelationship->getAttribute('Type'));

            if (strcasecmp($sourceRelationship->getAttribute('TargetMode'), 'External') === 0) {
                $relationship->setAttribute('Target', $sourceRelationship->getAttribute('Target'));
                $relationship->setAttribute('TargetMode', 'External');
            } else {
                $sourcePart = $this->resolvePartPath('word/document.xml', $sourceRelationship->getAttribute('Target'));
                $mapKey = $sourceNumber.'|'.$sourcePart;
                $destinationPart = $this->copiedParts[$mapKey]
                    ?? $this->allocatePartName($target, $sourcePart, $sourceNumber);
                $this->copyPartGraph(
                    $source,
                    $target,
                    $sourceContentTypes,
                    $baseContentTypes,
                    $sourcePart,
                    $destinationPart,
                    $styleMap,
                    $numberMap,
                    $sourceNumber
                );
                $relationship->setAttribute('Target', $this->relativePartPath('word/document.xml', $destinationPart));
            }
            $baseRelationships->documentElement->appendChild($relationship);
            foreach ($attributes as $attribute) {
                $attribute->value = $newId;
            }
        }
    }

    private function copyPartGraph(
        ZipArchive $source,
        ZipArchive $target,
        DOMDocument $sourceContentTypes,
        DOMDocument $baseContentTypes,
        string $sourcePart,
        string $destinationPart,
        array $styleMap,
        array $numberMap,
        int $sourceNumber
    ): void {
        $mapKey = $sourceNumber.'|'.$sourcePart;
        if (isset($this->copiedParts[$mapKey])) {
            return;
        }
        $this->copiedParts[$mapKey] = $destinationPart;
        $bytes = $source->getFromName($sourcePart);
        if ($bytes !== false && str_ends_with(strtolower($sourcePart), '.xml')) {
            $part = $this->loadXml($bytes);
            $this->applyValueMaps($part, $styleMap, $numberMap);
            $bytes = $part->saveXML();
        }
        if ($bytes === false || ! $target->addFromString($destinationPart, $bytes)) {
            throw new RuntimeException('Không sao chép được thành phần Word: '.$sourcePart);
        }
        $this->copyContentType($sourceContentTypes, $baseContentTypes, $sourcePart, $destinationPart);

        $sourceRelsPath = $this->relationshipPartPath($sourcePart);
        $relationshipsXml = $source->getFromName($sourceRelsPath);
        if ($relationshipsXml === false) {
            return;
        }
        $partRelationships = $this->loadXml($relationshipsXml);
        foreach ($partRelationships->getElementsByTagNameNS(self::PACKAGE_R, 'Relationship') as $relationship) {
            if (strcasecmp($relationship->getAttribute('TargetMode'), 'External') === 0) {
                continue;
            }
            $nestedSource = $this->resolvePartPath($sourcePart, $relationship->getAttribute('Target'));
            $nestedKey = $sourceNumber.'|'.$nestedSource;
            $nestedDestination = $this->copiedParts[$nestedKey]
                ?? $this->allocatePartName($target, $nestedSource, $sourceNumber);
            $this->copyPartGraph(
                $source,
                $target,
                $sourceContentTypes,
                $baseContentTypes,
                $nestedSource,
                $nestedDestination,
                $styleMap,
                $numberMap,
                $sourceNumber
            );
            $relationship->setAttribute('Target', $this->relativePartPath($destinationPart, $nestedDestination));
        }
        $this->writeXml($target, $this->relationshipPartPath($destinationPart), $partRelationships);
    }

    private function applyValueMaps(DOMDocument $dom, array $styleMap, array $numberMap): void
    {
        foreach (['pStyle', 'rStyle', 'tblStyle'] as $name) {
            $this->replaceValues($dom, $name, $styleMap);
        }
        $this->replaceValues($dom, 'numId', $numberMap);
    }

    private function applyDefaultStyles(DOMDocument $dom, array $defaults): void
    {
        if (isset($defaults['paragraph'])) {
            foreach ($dom->getElementsByTagNameNS(self::W, 'p') as $paragraph) {
                $properties = $this->directChild($paragraph, 'pPr');
                if ($properties && $this->directChild($properties, 'pStyle')) {
                    continue;
                }
                if (! $properties) {
                    $properties = $dom->createElementNS(self::W, 'w:pPr');
                    $paragraph->insertBefore($properties, $paragraph->firstChild);
                }
                $style = $dom->createElementNS(self::W, 'w:pStyle');
                $style->setAttributeNS(self::W, 'w:val', $defaults['paragraph']);
                $properties->insertBefore($style, $properties->firstChild);
            }
        }
        if (isset($defaults['table'])) {
            foreach ($dom->getElementsByTagNameNS(self::W, 'tbl') as $table) {
                $properties = $this->directChild($table, 'tblPr');
                if ($properties && $this->directChild($properties, 'tblStyle')) {
                    continue;
                }
                if (! $properties) {
                    $properties = $dom->createElementNS(self::W, 'w:tblPr');
                    $table->insertBefore($properties, $table->firstChild);
                }
                $style = $dom->createElementNS(self::W, 'w:tblStyle');
                $style->setAttributeNS(self::W, 'w:val', $defaults['table']);
                $properties->insertBefore($style, $properties->firstChild);
            }
        }
    }

    private function directChild(DOMElement $parent, string $name): ?DOMElement
    {
        foreach ($parent->childNodes as $child) {
            if ($child instanceof DOMElement && $child->namespaceURI === self::W && $child->localName === $name) {
                return $child;
            }
        }

        return null;
    }

    private function replaceValues(DOMNode $scope, string $elementName, array $map, string $attributeName = 'val'): void
    {
        if ($map === []) {
            return;
        }
        $document = $scope instanceof DOMDocument ? $scope : $scope->ownerDocument;
        if (! $document) {
            return;
        }
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', self::W);
        $context = $scope instanceof DOMDocument ? $document->documentElement : $scope;
        foreach ($xpath->query('.//w:'.$elementName.'[@w:'.$attributeName.']', $context) ?: [] as $element) {
            if (! $element instanceof DOMElement) {
                continue;
            }
            $old = $element->getAttributeNS(self::W, $attributeName);
            if (isset($map[$old])) {
                $element->setAttributeNS(self::W, 'w:'.$attributeName, $map[$old]);
            }
        }
    }

    private function remapDocumentIds(DOMDocument $dom, int &$nextBookmarkId, int &$nextDrawingId, int &$nextContentControlId): void
    {
        $bookmarkMap = [];
        foreach ($dom->getElementsByTagNameNS(self::W, 'bookmarkStart') as $bookmark) {
            $old = $bookmark->getAttributeNS(self::W, 'id');
            $bookmarkMap[$old] = (string) $nextBookmarkId++;
            $bookmark->setAttributeNS(self::W, 'w:id', $bookmarkMap[$old]);
        }
        foreach ($dom->getElementsByTagNameNS(self::W, 'bookmarkEnd') as $bookmark) {
            $old = $bookmark->getAttributeNS(self::W, 'id');
            if (isset($bookmarkMap[$old])) {
                $bookmark->setAttributeNS(self::W, 'w:id', $bookmarkMap[$old]);
            }
        }
        foreach ($dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing', 'docPr') as $drawing) {
            $drawing->setAttribute('id', (string) $nextDrawingId++);
        }
        foreach ($dom->getElementsByTagNameNS(self::W, 'sdtPr') as $properties) {
            foreach ($properties->childNodes as $child) {
                if ($child instanceof DOMElement && $child->namespaceURI === self::W && $child->localName === 'id') {
                    $child->setAttributeNS(self::W, 'w:val', (string) $nextContentControlId++);
                    break;
                }
            }
        }
    }

    private function endCurrentSection(DOMDocument $main, DOMElement $body, DOMElement $sourceSection): void
    {
        $section = $main->importNode($sourceSection, true);
        if (! $section instanceof DOMElement) {
            throw new RuntimeException('Không đọc được thiết lập trang của biểu mẫu.');
        }
        $this->setSectionType($main, $section, 'nextPage');

        $last = $body->lastChild;
        if (! $last instanceof DOMElement || $last->namespaceURI !== self::W || $last->localName !== 'p') {
            $last = $main->createElementNS(self::W, 'w:p');
            $body->appendChild($last);
        }
        $paragraphProperties = null;
        foreach ($last->childNodes as $child) {
            if ($child instanceof DOMElement && $child->namespaceURI === self::W && $child->localName === 'pPr') {
                $paragraphProperties = $child;
                break;
            }
        }
        if (! $paragraphProperties) {
            $paragraphProperties = $main->createElementNS(self::W, 'w:pPr');
            $last->insertBefore($paragraphProperties, $last->firstChild);
        }
        foreach (iterator_to_array($paragraphProperties->childNodes) as $child) {
            if ($child instanceof DOMElement && $child->namespaceURI === self::W && $child->localName === 'sectPr') {
                $paragraphProperties->removeChild($child);
            }
        }
        $paragraphProperties->appendChild($section);
    }

    private function blankSectionParagraph(
        DOMDocument $main,
        DOMElement $sourceSection,
        string $headerId,
        string $footerId
    ): DOMElement {
        $paragraph = $main->createElementNS(self::W, 'w:p');
        $paragraphProperties = $main->createElementNS(self::W, 'w:pPr');
        $section = $main->createElementNS(self::W, 'w:sectPr');

        foreach ([['headerReference', 'default', $headerId], ['footerReference', 'default', $footerId]] as [$name, $type, $id]) {
            $reference = $main->createElementNS(self::W, 'w:'.$name);
            $reference->setAttributeNS(self::W, 'w:type', $type);
            $reference->setAttributeNS(self::R, 'r:id', $id);
            $section->appendChild($reference);
        }
        $type = $main->createElementNS(self::W, 'w:type');
        $type->setAttributeNS(self::W, 'w:val', 'nextPage');
        $section->appendChild($type);
        foreach (['pgSz', 'pgMar', 'paperSrc', 'cols', 'docGrid'] as $name) {
            foreach ($sourceSection->childNodes as $child) {
                if ($child instanceof DOMElement && $child->namespaceURI === self::W && $child->localName === $name) {
                    $section->appendChild($main->importNode($child, true));
                    break;
                }
            }
        }
        $paragraphProperties->appendChild($section);
        $paragraph->appendChild($paragraphProperties);

        return $paragraph;
    }

    private function addBlankHeaderAndFooter(
        ZipArchive $target,
        DOMDocument $relationships,
        DOMDocument $contentTypes
    ): array {
        $headerPart = 'word/headerBundleBlank.xml';
        $footerPart = 'word/footerBundleBlank.xml';
        $headerId = 'rIdBundle'.$this->relationshipSequence++;
        $footerId = 'rIdBundle'.$this->relationshipSequence++;
        $target->addFromString($headerPart, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:hdr xmlns:w="'.self::W.'"><w:p/></w:hdr>');
        $target->addFromString($footerPart, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:ftr xmlns:w="'.self::W.'"><w:p/></w:ftr>');
        foreach ([
            [$headerId, self::HEADER_REL, 'headerBundleBlank.xml'],
            [$footerId, self::FOOTER_REL, 'footerBundleBlank.xml'],
        ] as [$id, $type, $part]) {
            $relationship = $relationships->createElementNS(self::PACKAGE_R, 'Relationship');
            $relationship->setAttribute('Id', $id);
            $relationship->setAttribute('Type', $type);
            $relationship->setAttribute('Target', $part);
            $relationships->documentElement->appendChild($relationship);
        }
        $this->addOverride($contentTypes, '/'.$headerPart, 'application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml');
        $this->addOverride($contentTypes, '/'.$footerPart, 'application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml');

        return [$headerId, $footerId];
    }

    private function setSectionType(DOMDocument $main, DOMElement $section, string $value): void
    {
        foreach (iterator_to_array($section->childNodes) as $child) {
            if ($child instanceof DOMElement && $child->namespaceURI === self::W && $child->localName === 'type') {
                $section->removeChild($child);
            }
        }
        $type = $main->createElementNS(self::W, 'w:type');
        $type->setAttributeNS(self::W, 'w:val', $value);
        $insertBefore = null;
        foreach ($section->childNodes as $child) {
            if ($child instanceof DOMElement && ! in_array($child->localName, ['headerReference', 'footerReference'], true)) {
                $insertBefore = $child;
                break;
            }
        }
        $section->insertBefore($type, $insertBefore);
    }

    private function finalSectionProperties(DOMElement $body): DOMElement
    {
        for ($node = $body->lastChild; $node; $node = $node->previousSibling) {
            if ($node instanceof DOMElement && $node->namespaceURI === self::W && $node->localName === 'sectPr') {
                return $node;
            }
        }
        throw new RuntimeException('Một biểu mẫu thiếu thiết lập trang Word.');
    }

    private function copyContentType(
        DOMDocument $source,
        DOMDocument $base,
        string $sourcePart,
        string $destinationPart
    ): void {
        foreach ($source->getElementsByTagNameNS(self::CONTENT_TYPES, 'Override') as $override) {
            if ($override->getAttribute('PartName') === '/'.$sourcePart) {
                $this->addOverride($base, '/'.$destinationPart, $override->getAttribute('ContentType'));

                return;
            }
        }
        $extension = strtolower(pathinfo($sourcePart, PATHINFO_EXTENSION));
        foreach ($source->getElementsByTagNameNS(self::CONTENT_TYPES, 'Default') as $default) {
            if (strtolower($default->getAttribute('Extension')) !== $extension) {
                continue;
            }
            foreach ($base->getElementsByTagNameNS(self::CONTENT_TYPES, 'Default') as $existing) {
                if (strtolower($existing->getAttribute('Extension')) === $extension) {
                    if ($existing->getAttribute('ContentType') !== $default->getAttribute('ContentType')) {
                        $this->addOverride($base, '/'.$destinationPart, $default->getAttribute('ContentType'));
                    }

                    return;
                }
            }
            $clone = $base->createElementNS(self::CONTENT_TYPES, 'Default');
            $clone->setAttribute('Extension', $default->getAttribute('Extension'));
            $clone->setAttribute('ContentType', $default->getAttribute('ContentType'));
            $base->documentElement->appendChild($clone);

            return;
        }
    }

    private function addOverride(DOMDocument $contentTypes, string $partName, string $contentType): void
    {
        foreach ($contentTypes->getElementsByTagNameNS(self::CONTENT_TYPES, 'Override') as $override) {
            if ($override->getAttribute('PartName') === $partName) {
                return;
            }
        }
        $override = $contentTypes->createElementNS(self::CONTENT_TYPES, 'Override');
        $override->setAttribute('PartName', $partName);
        $override->setAttribute('ContentType', $contentType);
        $contentTypes->documentElement->appendChild($override);
    }

    private function relationshipById(DOMDocument $relationships, string $id): ?DOMElement
    {
        foreach ($relationships->getElementsByTagNameNS(self::PACKAGE_R, 'Relationship') as $relationship) {
            if ($relationship->getAttribute('Id') === $id) {
                return $relationship;
            }
        }

        return null;
    }

    private function allocatePartName(ZipArchive $target, string $sourcePart, int $sourceNumber): string
    {
        $extension = pathinfo($sourcePart, PATHINFO_EXTENSION);
        $baseName = pathinfo($sourcePart, PATHINFO_FILENAME);
        do {
            $sequence = ++$this->partSequence;
            $candidate = match (true) {
                str_starts_with($sourcePart, 'word/media/') => 'word/media/bundle'.$sourceNumber.'-'.$sequence.'.'.$extension,
                str_starts_with($baseName, 'header') => 'word/headerBundle'.$sourceNumber.'-'.$sequence.'.xml',
                str_starts_with($baseName, 'footer') => 'word/footerBundle'.$sourceNumber.'-'.$sequence.'.xml',
                default => 'word/bundle'.$sourceNumber.'/'.$sequence.'-'.basename($sourcePart),
            };
        } while ($target->locateName($candidate) !== false);

        return $candidate;
    }

    private function resolvePartPath(string $ownerPart, string $target): string
    {
        $path = str_starts_with($target, '/') ? ltrim($target, '/') : dirname($ownerPart).'/'.$target;
        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
            } else {
                $segments[] = $segment;
            }
        }

        return implode('/', $segments);
    }

    private function relativePartPath(string $ownerPart, string $targetPart): string
    {
        $from = explode('/', trim(dirname($ownerPart), '/'));
        $to = explode('/', trim($targetPart, '/'));
        while ($from && $to && $from[0] === $to[0]) {
            array_shift($from);
            array_shift($to);
        }

        return str_repeat('../', count($from)).implode('/', $to);
    }

    private function relationshipPartPath(string $part): string
    {
        return dirname($part).'/_rels/'.basename($part).'.rels';
    }

    private function highestRelationshipNumber(DOMDocument $relationships): int
    {
        $highest = 0;
        foreach ($relationships->getElementsByTagNameNS(self::PACKAGE_R, 'Relationship') as $relationship) {
            if (preg_match('/(\d+)$/', $relationship->getAttribute('Id'), $matches)) {
                $highest = max($highest, (int) $matches[1]);
            }
        }

        return $highest;
    }

    private function highestAttribute(?DOMDocument $dom, string $element, string $attribute, bool $allowNegative = false): int
    {
        $highest = 0;
        if (! $dom) {
            return $highest;
        }
        foreach ($dom->getElementsByTagNameNS(self::W, $element) as $node) {
            $value = (int) $node->getAttributeNS(self::W, $attribute);
            if ($allowNegative && $value < 0) {
                continue;
            }
            $highest = max($highest, $value);
        }

        return $highest;
    }

    private function highestDrawingId(DOMDocument $dom): int
    {
        $highest = 0;
        foreach ($dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing', 'docPr') as $node) {
            $highest = max($highest, (int) $node->getAttribute('id'));
        }

        return $highest;
    }

    private function highestContentControlId(DOMDocument $dom): int
    {
        $highest = 0;
        foreach ($dom->getElementsByTagNameNS(self::W, 'sdtPr') as $properties) {
            foreach ($properties->childNodes as $child) {
                if ($child instanceof DOMElement && $child->namespaceURI === self::W && $child->localName === 'id') {
                    $highest = max($highest, (int) $child->getAttributeNS(self::W, 'val'));
                }
            }
        }

        return $highest;
    }

    private function xmlFromZip(ZipArchive $zip, string $name): DOMDocument
    {
        $xml = $zip->getFromName($name);
        if ($xml === false) {
            throw new RuntimeException('Tệp Word thiếu thành phần '.$name.'.');
        }

        return $this->loadXml($xml);
    }

    private function optionalXmlFromZip(ZipArchive $zip, string $name): ?DOMDocument
    {
        $xml = $zip->getFromName($name);

        return $xml === false ? null : $this->loadXml($xml);
    }

    private function loadXml(string $xml): DOMDocument
    {
        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        try {
            if (! $dom->loadXML($xml, LIBXML_NONET)) {
                throw new RuntimeException('XML trong tệp Word không hợp lệ.');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        return $dom;
    }

    private function writeXml(ZipArchive $zip, string $name, DOMDocument $dom): void
    {
        if (! $zip->addFromString($name, $dom->saveXML())) {
            throw new RuntimeException('Không ghi được thành phần Word '.$name.'.');
        }
    }
}
