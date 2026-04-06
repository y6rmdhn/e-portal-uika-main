<x-mail::message>
<div style="background-color: #111827; padding: 40px; border-radius: 12px; border: 1px solid #374151; font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
<div style="text-align: center; margin-bottom: 30px;">
<h2 style="color: #ffffff; font-size: 24px; font-weight: bold; margin: 0; letter-spacing: 1px;">E-PORTAL UIKA</h2>
<p style="color: #9ca3af; font-size: 14px; margin-top: 5px;
</div>

@if (! empty($greeting))
<h1 style="color: #ffffff; font-size: 20px; font-weight: bold; margin-bottom: 15px;">{{ $greeting }}</h1>
@else
@if ($level === 'error')
<h1 style="color: #ef4444; font-size: 20px; font-weight: bold; margin-bottom: 15px;">@lang('Whoops!')</h1>
@else
<h1 style="color: #ffffff; font-size: 20px; font-weight: bold; margin-bottom: 15px;">@lang('Hello!')</h1>
@endif
@endif

@foreach ($introLines as $line)
<p style="color: #d1d5db; font-size: 16px; line-height: 1.6; margin-bottom: 15px;">{{ $line }}</p>
@endforeach

@isset($actionText)
<div style="text-align: center; margin: 35px 0;">
<a href="{{ $actionUrl }}" style="background-color: #1f2937; color: #ffffff; padding: 14px 32px; border-radius: 8px; text-decoration: none; font-weight: bold; display: inline-block; border: 1px solid #4b5563;">{{ $actionText }}</a>
</div>
@endisset

@foreach ($outroLines as $line)
<p style="color: #d1d5db; font-size: 16px; line-height: 1.6; margin-bottom: 15px;">{{ $line }}</p>
@endforeach

<p style="color: #9ca3af; font-size: 16px; margin-top: 30px; line-height: 1.6;">
@if (! empty($salutation))
{{ $salutation }}
@else
@lang('Regards'),<br>
<strong style="color: #ffffff;">{{ config('app.name') }}</strong>
@endif
</p>

@isset($actionText)
<hr style="border: none; border-top: 1px dashed #4b5563; margin: 30px 0;">
<p style="color: #6b7280; font-size: 13px; line-height: 1.6;">
@lang(
"If you're having trouble clicking the \":actionText\" button, copy and paste the URL below into your web browser:",
['actionText' => $actionText]
)
<br><br>
<a href="{{ $actionUrl }}" style="color: #60a5fa; text-decoration: none; word-break: break-all;">{{ $actionUrl }}</a>
</p>
@endisset
</div>
</x-mail::message>