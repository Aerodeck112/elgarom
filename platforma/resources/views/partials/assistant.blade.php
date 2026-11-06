@if (\App\Services\Ai\Assistant::publicStatus()['enabled'])
<button class="ai-launch" type="button" data-ai-open aria-haspopup="dialog" aria-controls="ai-panel">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 5h16v11H9l-5 4z"/><path d="M8 10h8M8 13h5"/></svg>
    Întrebați asistentul AI
</button>
<section class="ai-panel" id="ai-panel" role="dialog" aria-modal="false" aria-labelledby="ai-title" hidden data-ai-panel>
    <div class="ai-panel__head">
        <div>
            <h2 id="ai-title">Asistent AI Elgarom</h2>
            <p>Discutați cu un program AI, nu cu o persoană. Răspunsurile se bazează pe informațiile publicate de Elgarom și pot conține erori. Pentru decizii tehnice, confirmați cu echipa.</p>
        </div>
        <button type="button" data-ai-close aria-label="Închide asistentul"><svg width="22" height="22" viewBox="0 0 22 22" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M5 5l12 12M17 5L5 17"/></svg></button>
    </div>
    <div class="ai-log" data-ai-log aria-live="polite">
        <p class="ai-msg ai-msg--assistant">Bună ziua! Vă pot explica serviciile și procesul de zincare Elgarom și vă pot ajuta să pregătiți o cerere de ofertă. Nu pot da prețuri, termene sau garanții.</p>
    </div>
    <form class="ai-form" data-ai-form>
        <label class="sr-only" for="ai-input">Mesajul dumneavoastră</label>
        <textarea id="ai-input" name="message" rows="1" maxlength="1500" placeholder="Scrieți o întrebare…" required></textarea>
        <button class="btn btn--sm" type="submit">Trimite</button>
    </form>
    <div class="ai-foot">
        <span>Conversația se șterge automat după {{ (int) setting('ai.retention_days', 30) }} de zile.</span>
        <button type="button" data-ai-delete>Șterge conversația</button>
    </div>
</section>
@endif
