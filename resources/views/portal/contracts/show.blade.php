@extends('layouts.portal')

@section('title', $contract->title)
@section('meta_description', $contract->title.' — '.$contract->activity_type.'. تفاصيل الفرصة الاستثمارية في أساس.')

@section('content')
    @php $return = config('app.show_public_returns') && $contract->expected_return !== null
        ? rtrim(rtrim($contract->expected_return, '0'), '.').'%'
        : 'عند الطلب'; @endphp

    <x-ip.page-header :title="$contract->title" :subtitle="$contract->activity_type">
        <x-slot:actions>
            <a href="{{ route('contracts.index') }}" style="color: var(--ip-muted); font-size:13px;">→ كل العقود</a>
        </x-slot:actions>
    </x-ip.page-header>

    <x-ip.card>
        <div class="ip-contract__head" style="margin-bottom:18px;">
            <div class="ip-card__title" style="font-size:18px;">تفاصيل الفرصة</div>
            <x-ip.status-pill :color="$contract->status_color" :label="$contract->status_label" />
        </div>

        <div class="ip-detail">
            <div>
                <div class="ip-detail__label">العائد المتوقع</div>
                <div class="ip-detail__value">{{ $return }}</div>
            </div>
            <div>
                <div class="ip-detail__label">مدة الاستثمار</div>
                <div class="ip-detail__value">{{ $contract->duration_months }} شهرًا</div>
            </div>
            <div>
                <div class="ip-detail__label">النصاب المستهدف</div>
                <div class="ip-detail__value">{{ money($contract->target_amount) }}</div>
            </div>
            <div>
                <div class="ip-detail__label">الحد الأدنى للمشاركة</div>
                <div class="ip-detail__value">{{ money($contract->min_amount) }}</div>
            </div>
            <div>
                <div class="ip-detail__label">الحد الأقصى للمشاركة</div>
                <div class="ip-detail__value">{{ $contract->max_amount !== null ? money($contract->max_amount) : '—' }}</div>
            </div>
        </div>
    </x-ip.card>

    @if ($contract->description)
        <x-ip.section-header title="عن العقد" />
        <x-ip.card>
            <p class="ip-prose">{{ $contract->description }}</p>
        </x-ip.card>
    @endif

    <x-ip.section-header title="المشاركة في هذا العقد" />
    <x-ip.card>
        @if (session('status'))
            <div class="ip-banner ip-banner--success" style="margin:0 0 12px;">
                <span class="ip-banner__icon"><i class="ti ti-circle-check"></i></span>
                <span>{{ session('status') }}</span>
            </div>
        @endif
        @if (session('error'))
            <div class="ip-banner ip-banner--warning" style="margin:0 0 12px;">
                <span class="ip-banner__icon"><i class="ti ti-alert-triangle"></i></span>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @guest
            <p class="ip-prose">للاطلاع على المزيد والمشاركة في هذه الفرصة، يرجى تسجيل الدخول إلى حسابك.</p>
            <div style="margin-top:12px;"><a href="{{ route('login') }}" class="ip-btn">دخول للتسجيل</a></div>
        @elseif (! auth()->user()->hasVerifiedEmail())
            <div class="ip-banner ip-banner--warning" style="margin:0;">
                <span class="ip-banner__icon"><i class="ti ti-mail-exclamation"></i></span>
                <span>وثّق بريدك الإلكتروني للمشاركة في هذه الفرصة. يمكنك تصفّح كل العقود الآن.</span>
            </div>
            <div style="margin-top:12px;"><a href="{{ route('verification.notice') }}" class="ip-btn">توثيق البريد الإلكتروني</a></div>
        @elseif ($investment)
            <div class="ip-banner ip-banner--success" style="margin:0;">
                <span class="ip-banner__icon"><i class="ti ti-circle-check"></i></span>
                <span>أنت مشارك في هذا العقد بالفعل.</span>
            </div>
            <div class="ip-kv__row" style="border-bottom:0; padding:10px 0 0;">
                <span class="ip-kv__label">حالة مشاركتك</span>
                <x-ip.status-pill :color="$investment->status_color" :label="$investment->status_label" />
            </div>
            <div style="margin-top:12px;"><a href="{{ route('portal.investments.show', $investment) }}" class="ip-btn">عرض مشاركتي</a></div>
        @elseif (! $canInvest)
            <div class="ip-banner ip-banner--warning" style="margin:0;">
                <span class="ip-banner__icon"><i class="ti ti-lock"></i></span>
                <span>يجب اكتمال التحقق من هويتك (KYC) قبل إبداء الاهتمام أو المشاركة.</span>
            </div>
            <div style="margin-top:12px;"><a href="{{ route('portal.profile') }}" class="ip-btn">عرض حالة التحقق</a></div>
        @elseif ($interest && in_array($interest->status->value, ['pending', 'contacted'], true))
            <div class="ip-kv__row" style="border-bottom:0; padding:0;">
                <span class="ip-kv__label">حالة طلب اهتمامك</span>
                <x-ip.status-pill :color="$interest->status_color" :label="$interest->status_label" />
            </div>
            <p class="ip-note" style="margin-top:10px;">أبديتَ اهتمامك بهذا العقد بتاريخ {{ $interest->created_at?->format('Y-m-d') }}. سنتواصل معك قريبًا.</p>
        @else
            <div x-data="{ open: false }">
                <button type="button" class="ip-btn" @click="open = true">إبداء اهتمام</button>

                {{-- Interest modal --}}
                <template x-if="open">
                    <div>
                        <div class="ip-drawer-backdrop" @click="open = false"></div>
                        <div class="ip-modal">
                            <div class="ip-modal__head">
                                <span class="ip-card__title" style="font-size:16px;">إبداء اهتمام بـ «{{ $contract->title }}»</span>
                                <button type="button" class="ip-iconbtn" @click="open = false" aria-label="إغلاق"><i class="ti ti-x"></i></button>
                            </div>
                            <form method="POST" action="{{ route('portal.contracts.interest', $contract) }}">
                                @csrf
                                <div class="ip-form-group">
                                    <label class="ip-label" for="notes">ملاحظات (اختياري)</label>
                                    <textarea id="notes" name="notes" rows="3" class="ip-input" placeholder="أخبرنا بما يهمّك حول هذه الفرصة...">{{ old('notes') }}</textarea>
                                </div>
                                <label class="ip-checkrow" style="margin-bottom:16px;">
                                    <input type="checkbox" name="confirm" value="1" class="ip-checkbox" required>
                                    <span>أؤكد رغبتي في إبداء الاهتمام بهذه الفرصة الاستثمارية.</span>
                                </label>
                                <button type="submit" class="ip-btn ip-btn--block">إرسال الطلب</button>
                            </form>
                        </div>
                    </div>
                </template>
            </div>
        @endguest
    </x-ip.card>
@endsection
