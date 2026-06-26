@extends('layouts.portal')

@section('title', 'الجديد في أساس')

@section('content')
    <x-ip.page-header title="الجديد في أساس" subtitle="آخر التحديثات والإعلانات من فريق أساس." />

    @if ($news->isEmpty())
        <x-ip.card>
            <x-ip.empty-state
                icon="ti-news-off"
                title="لا توجد أخبار"
                description="لا توجد تحديثات منشورة حتى الآن. تابعنا لمعرفة الجديد." />
        </x-ip.card>
    @else
        <x-ip.card style="padding:0;">
            @foreach ($news as $item)
                <x-ip.news-item
                    :title="$item->title"
                    :excerpt="\Illuminate\Support\Str::limit(strip_tags($item->body), 180)"
                    :publishedDate="$item->published_at?->format('Y-m-d')" />
            @endforeach
        </x-ip.card>

        <div class="ip-pagination">{{ $news->onEachSide(1)->links() }}</div>
    @endif
@endsection
