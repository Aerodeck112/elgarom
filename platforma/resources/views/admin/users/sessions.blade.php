@if ($sessions->isEmpty())<p class="muted small">Nicio sesiune activă.</p>@else
<div class="table-wrap"><table class="t"><thead><tr><th>Ultima activitate</th><th>IP</th><th>Browser</th></tr></thead><tbody>
@foreach ($sessions as $s)<tr><td class="small">{{ \Carbon\Carbon::createFromTimestamp($s->last_activity)->format('d.m.Y H:i') }}@if ($s->id === session()->getId()) <span class="tag tag--info">sesiunea curentă</span>@endif</td><td class="small">{{ $s->ip_address }}</td><td class="small">{{ \Illuminate\Support\Str::limit($s->user_agent, 90) }}</td></tr>@endforeach
</tbody></table></div>@endif
