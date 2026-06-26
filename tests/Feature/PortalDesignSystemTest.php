<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The portal design-system components are purely presentational — they render
 * from passed props only (no models, queries, services or business logic).
 */
class PortalDesignSystemTest extends TestCase
{
    public function test_stat_card_renders_label_and_value(): void
    {
        $this->blade('<x-ip.stat-card color="success" label="الأرباح" value="4,500 ر.س" trend="12%" />')
            ->assertSee('الأرباح')
            ->assertSee('4,500 ر.س')
            ->assertSee('ip-stat--success', false);
    }

    public function test_status_pill_uses_token_variant(): void
    {
        $this->blade('<x-ip.status-pill color="warning" label="قيد الاعتماد" />')
            ->assertSee('قيد الاعتماد')
            ->assertSee('ip-pill--warning', false);
    }

    public function test_hero_balance_renders(): void
    {
        $this->blade('<x-ip.hero-balance title="رأس المال" value="150,000 ر.س" description="نشطة" />')
            ->assertSee('رأس المال')
            ->assertSee('150,000 ر.س');
    }

    public function test_contract_card_renders_with_nested_status_pill(): void
    {
        $this->blade('<x-ip.contract-card title="صندوق النمو" activity="تجارة" status-label="مفتوح" status-color="success" />')
            ->assertSee('صندوق النمو')
            ->assertSee('مفتوح')
            ->assertSee('ip-pill--success', false);
    }

    public function test_timeline_item_dot_uses_color_token(): void
    {
        $this->blade('<x-ip.timeline-item color="info" title="تم الاعتماد" date="اليوم" />')
            ->assertSee('تم الاعتماد')
            ->assertSee('var(--ip-info-700)', false);
    }

    public function test_payout_row_renders(): void
    {
        $this->blade('<x-ip.payout-row amount="1,500 ر.س" due-date="2026-07-01" type="ربح" status-label="مدفوعة" status-color="success" />')
            ->assertSee('1,500 ر.س')
            ->assertSee('ربح')
            ->assertSee('مدفوعة');
    }

    public function test_news_and_notification_items_render(): void
    {
        $this->blade('<x-ip.news-item title="خبر" excerpt="نص" published-date="2026-06-20" />')->assertSee('خبر');

        $this->blade('<x-ip.notification-item :unread="true" title="إشعار" description="وصف" />')
            ->assertSee('إشعار')
            ->assertSee('ip-notif--unread', false);
    }

    public function test_layout_and_container_components_render(): void
    {
        $this->blade('<x-ip.section-header title="قسم" subtitle="وصف" />')->assertSee('قسم');
        $this->blade('<x-ip.chart-card title="مخطط" />')->assertSee('مخطط')->assertSee('مساحة المخطط');
        $this->blade('<x-ip.empty-state title="فارغ" description="لا بيانات" />')->assertSee('فارغ');
        $this->blade('<x-ip.tab-group :tabs="[\'الكل\', \'مدفوعة\']" />')->assertSee('الكل')->assertSee('مدفوعة');
    }

    public function test_data_table_wraps_slots(): void
    {
        $this->blade('<x-ip.data-table><tr><td>صف</td></tr></x-ip.data-table>')
            ->assertSee('صف')
            ->assertSee('ip-table', false);
    }

    public function test_skeleton_renders_each_type(): void
    {
        foreach (['card', 'table', 'chart', 'profile', 'notification'] as $type) {
            $this->blade('<x-ip.skeleton type="'.$type.'" />')->assertSee('ip-skeleton', false);
        }
    }
}
