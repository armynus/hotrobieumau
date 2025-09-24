<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\FormDraft;
use Illuminate\Support\Facades\Session;

class FormDraftController extends Controller
{
    public function save(Request $request)
    {
        $userId = Session::get('user_id');
        if (!$userId) return response()->json(['error'=>'Unauthorized'], 401);

        $formKey = $request->input('form_key') ?? 'supportFormType';
        $payloadRaw = $request->input('payload', '{}');
        $payload = is_string($payloadRaw) ? json_decode($payloadRaw, true) : $payloadRaw;

        FormDraft::updateOrCreate(
            ['user_id' => $userId, 'form_key' => $formKey],
            ['payload' => $payload, 'updated_at' => now()]
        );

        return response()->json(['ok' => true]);
    }

    public function get($formKey)
    {
        $userId = Session::get('user_id');
        if (!$userId) return response()->json(null, 204);

        $draft = FormDraft::where('user_id', $userId)->where('form_key', $formKey)->first();
        if (!$draft) return response()->json(null, 204);

        return response()->json(['payload' => $draft->payload]);
    }
}
