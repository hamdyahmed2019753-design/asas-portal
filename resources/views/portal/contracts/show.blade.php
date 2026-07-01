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
        @elseif ($investment && $investment->status->value !== 'rejected')
            @php $st = $investment->status->value; @endphp
            @if ($st === 'pending_payment')
                <div class="ip-banner ip-banner--warning" style="margin:0;">
                    <span class="ip-banner__icon"><i class="ti ti-clock-dollar"></i></span>
                    <span>لم يكتمل تحويلك بعد — أكمل رفع الإيصال لإتمام اشتراكك.</span>
                </div>
                <div style="margin-top:12px;"><a href="{{ route('portal.investments.transfer', $investment) }}" class="ip-btn"><i class="ti ti-arrow-left"></i> أكمل التحويل</a></div>
            @elseif ($st === 'payment_submitted')
                <div class="ip-banner ip-banner--info" style="margin:0;">
                    <span class="ip-banner__icon"><i class="ti ti-file-check"></i></span>
                    <span>تم استلام إيصال تحويلك وهو قيد المراجعة والاعتماد.</span>
                </div>
                <div style="margin-top:12px;"><a href="{{ route('portal.investments.show', $investment) }}" class="ip-btn">عرض مشاركتي</a></div>
            @else
                <div class="ip-banner ip-banner--success" style="margin:0;">
                    <span class="ip-banner__icon"><i class="ti ti-circle-check"></i></span>
                    <span>أنت مشارك في هذا العقد بالفعل.</span>
                </div>
                <div class="ip-kv__row" style="border-bottom:0; padding:10px 0 0;">
                    <span class="ip-kv__label">حالة مشاركتك</span>
                    <x-ip.status-pill :color="$investment->status_color" :label="$investment->status_label" />
                </div>
                <div style="margin-top:12px;"><a href="{{ route('portal.investments.show', $investment) }}" class="ip-btn">عرض مشاركتي</a></div>
            @endif
        @elseif (! $canInvest)
            <div class="ip-banner ip-banner--warning" style="margin:0;">
                <span class="ip-banner__icon"><i class="ti ti-lock"></i></span>
                <span>يجب اكتمال التحقق من هويتك (KYC) قبل المشاركة في العقد.</span>
            </div>
            <div style="margin-top:12px;"><a href="{{ route('portal.profile') }}" class="ip-btn">عرض حالة التحقق</a></div>
        @elseif (! $subscribable)
            <p class="ip-note" style="margin:0;">هذا العقد غير متاح للاشتراك المباشر حاليًا.</p>
        @else
            <div x-data="{ open: false, shares: {{ $shareBounds['min'] }}, price: {{ (float) $contract->share_price }},
                           get amount() { return (this.shares > 0 ? this.shares : 0) * this.price; } }">
                <button type="button" class="ip-btn" @click="open = true"><i class="ti ti-box"></i> اشتراك في العقد</button>

                {{-- Subscribe modal (no @click.outside on .ip-modal — backdrop handles it) --}}
                <template x-if="open">
                    <div>
                        <div class="ip-drawer-backdrop" @click="open = false"></div>
                        <div class="ip-modal">
                            <div class="ip-modal__head">
                                <span class="ip-card__title" style="font-size:16px;">اشتراك في «{{ $contract->title }}»</span>
                                <button type="button" class="ip-iconbtn" @click="open = false" aria-label="إغلاق"><i class="ti ti-x"></i></button>
                            </div>
                            <form method="POST" action="{{ route('portal.contracts.subscribe', $contract) }}">
                                @csrf
                                <div class="ip-form-group">
                                    <label class="ip-label" for="shares">عدد الحصص <span class="ip-note">(سعر الحصة {{ money($contract->share_price) }})</span></label>
                                    <input type="number" id="shares" name="shares" class="ip-input" x-model.number="shares"
                                           min="{{ $shareBounds['min'] }}" @if ($shareBounds['max']) max="{{ $shareBounds['max'] }}" @endif step="1" required>
                                    @error('shares')<div class="ip-note" style="color:var(--ip-danger-700); margin-top:6px;">{{ $message }}</div>@enderror
                                </div>
                                <div class="ip-kv__row" style="border-bottom:0; padding:4px 0 12px;">
                                    <span class="ip-kv__label">المبلغ الإجمالي</span>
                                    <span class="ip-kv__value"><span x-text="amount.toLocaleString('en-US')"></span> ر.س</span>
                                </div>
                                <button type="submit" class="ip-btn ip-btn--block">متابعة إلى التحويل</button>
                            </form>
                        </div>
                    </div>
                </template>
            </div>
        @endguest
    </x-ip.card>
@endsection
