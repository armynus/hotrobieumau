                                    @if($key == 'gender')
                                        <x-select-input-to-check-box 
                                            name="gender" 
                                            :options="$gender" 
                                            selected="{{ old('gender') }}" 
                                            {{-- placeholder="Chọn giới tính"  --}}
                                            :required="true" 
                                        />
                                    @elseif($key == 'nguoi')
                                        <x-select-input-to-check-box 
                                            name="nguoi" 
                                            :options="$nguoi" 
                                            selected="{{ old('nguoi') }}"  
                                            :required="true" 
                                        />
                                    
                                    @elseif($key == 'identity_type')
                                        <x-select-input-to-check-box 
                                            name="identity_type" 
                                            :options="$identity_type" 
                                            selected="{{ old('identity_type') }}"  
                                            :required="true" 
                                        />
                                    @elseif($key == 'identity_place')
                                        <x-select-input-to-check-box 
                                            name="identity_place" 
                                            :options="$identity_place" 
                                            selected="{{ old('identity_place') }}"  
                                            :required="true" 
                                        />
                                    @elseif($key == 'NoiCapCCCDMoi')
                                        <x-select-input-to-check-box 
                                            name="NoiCapCCCDMoi" 
                                            :options="$NoiCapCCCDMoi" 
                                            selected="{{ old('NoiCapCCCDMoi') }}"  
                                            :required="true" 
                                        />
                                    @elseif($key == 'NgheNghiepKH')
                                        <x-select-input-to-check-box 
                                            name="NgheNghiepKH" 
                                            :options="$NgheNghiepKH" 
                                            selected="{{ old('NgheNghiepKH') }}"  
                                            :required="true" 
                                        />
                                    @elseif($key == 'ChucVuKH')
                                        <x-select-input-to-check-box 
                                            name="ChucVuKH" 
                                            :options="$ChucVuKH" 
                                            selected="{{ old('ChucVuKH') }}"  
                                            :required="true" 
                                        />
                                    @elseif($key == 'ccycd')
                                        <x-select-input-to-check-box 
                                            name="ccycd" 
                                            :options="$ccycd" 
                                            selected="{{'VND' ?? old('ccycd')}}" 
                                            :required="true" 
                                        />
                                    @elseif($key == 'HangThe')
                                        <x-select-input-to-check-box 
                                            name="HangThe" 
                                            :options="$HangThe" 
                                            selected="{{ old('HangThe') }}" 
                                            :required="true" 
                                        />
                                    @elseif($key == 'LoaiThe')
                                        <x-select-input-to-check-box 
                                            name="LoaiThe" 
                                            :options="$LoaiThe" 
                                            selected="{{ old('LoaiThe') }}" 
                                            :required="true" 
                                        />
                                    @elseif($key == 'SoTKTT')
                                        <x-select-input-to-check-box 
                                            name="SoTKTT" 
                                            :options="$SoTKTT" 
                                            selected="{{ old('SoTKTT') }}" 
                                            :required="true" 
                                        />
                                    @elseif($key == 'ThuTuDong')
                                        <x-check-value-to-check-box
                                            name="ThuTuDong" 
                                            :options="$ThuTuDong" 
                                            selected="{{ old('ThuTuDong') }}" 
                                            :required="false" 
                                        />
                                    @elseif($key == 'MobileBanking')
                                        <x-check-value-to-check-box
                                            name="MobileBanking" 
                                            :options="$MobileBanking" 
                                            selected="{{ old('MobileBanking') }}" 
                                            :required="false" 
                                        />
                                    @elseif($key == 'RetaileBanking')
                                        <x-check-value-to-check-box
                                            name="RetaileBanking" 
                                            :options="$RetaileBanking" 
                                            selected="{{ old('RetaileBanking') }}" 
                                            :required="false" 
                                        />
                                    @elseif($key == 'DichVuKhac')
                                        <x-check-value-to-check-box
                                            name="DichVuKhac" 
                                            :options="$DichVuKhac" 
                                            selected="{{ old('DichVuKhac') }}" 
                                            :required="false" 
                                        />
                                    
                                    @else
                                        <input type="{{ $info['data_type'] ?? 'text' }}" 
                                            class="form-control" 
                                            id="{{ $key }}" 
                                            name="{{ $key }}" required
                                            placeholder="{{ $info['placeholder'] ?? '' }}"
                                            value="{{ 
                                                $info['value'] ?? (
                                                    $key == 'NgayThangNam' || $key == 'NgayGiaoDich' || $key == 'NgayUQ' || $key == 'NgayUQCQ' ? now()->format('Y-m-d') : 
                                                    ($key == 'QuocTich' ? 'Việt Nam' : 
                                                    ($key == 'NgayHen' ? now()->addDays(7)->format('Y-m-d') : 
                                                    ($key == 'branch' ? session('UserBranchName', '') : 
                                                    ($key == 'DiaChi' ? session('UserBranchAddr', '') : 
                                                    ($key == 'SoFax' ? session('UserBranchFax', '') : 
                                                    ($key == 'DienThoai' ? session('UserBranchPhone', '') : 
                                                    ($key == 'GDichVien' ? session('user_name', '') : 
                                                    ($key == 'DiaDanh' ? session('UserBranchPlace', '') : 
                                                    ($key == 'branch_code' ? session('UserBranchCode', '') : '')))))))))) 
                                            }}"
                                            {{ $key == 'SoThe' ? 'maxlength=4' : '' }}                                            
                                        >  
                                
                                    @endif
