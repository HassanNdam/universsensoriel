{{-- Tiroir « Langue et pays », ouvert par les boutons [data-ouvrir="langue-pays"] (en-tête, menu mobile, pied). --}}
@php
  $langues = App\langues();
  $pays = App\pays_et_devises();
  $reelles = array_filter($langues, fn ($l) => ! $l['auto']);
  $auto = array_filter($langues, fn ($l) => $l['auto']);
@endphp

<div class="tiroir tiroir-droite" id="langue-pays" hidden>
  <div class="tiroir-voile" data-fermer="langue-pays"></div>
  <div class="tiroir-panneau reglages" role="dialog" aria-modal="true" aria-labelledby="langue-pays-titre">
    <div class="tiroir-tete">
      <h2 class="reglages-titre" id="langue-pays-titre">Langue et pays</h2>
      <button class="entete-bouton" type="button" data-fermer="langue-pays">{!! App\icone('fermer', 24) !!}<span class="sr-only">Fermer</span></button>
    </div>

    <div class="reglages-corps">
      <section aria-labelledby="choix-langue-titre">
        <h3 class="surtitre" id="choix-langue-titre">Langue</h3>
        <ul class="choix-langues notranslate" translate="no" data-no-translation>
          @foreach ($reelles as $langue)
            <li>
              <a href="{{ $langue['url'] }}" lang="{{ $langue['code'] }}" hreflang="{{ $langue['code'] }}" data-langue="{{ $langue['code'] }}" @if ($langue['active']) aria-current="true" @endif>
                {{ $langue['nom'] }} {!! App\icone('coche', 18) !!}
              </a>
            </li>
          @endforeach
        </ul>
        @if ($auto)
          <p class="reglages-note">Traduction automatique</p>
          <ul class="choix-langues notranslate" translate="no" data-no-translation>
            @foreach ($auto as $langue)
              <li>
                <a href="{{ $langue['url'] }}" lang="{{ $langue['code'] }}" rel="nofollow" data-langue="{{ $langue['code'] }}" data-auto>
                  {{ $langue['nom'] }} {!! App\icone('coche', 18) !!}
                </a>
              </li>
            @endforeach
          </ul>
        @endif
      </section>

      @if ($pays)
        <section aria-labelledby="choix-pays-titre">
          <h3 class="surtitre" id="choix-pays-titre">Pays de livraison</h3>
          <form class="choix-pays" method="post">
            <label class="sr-only" for="choix-pays">Pays</label>
            <select id="choix-pays" name="wcpbc-manual-country">
              @foreach ($pays as $p)
                <option value="{{ $p['code'] }}" @selected($p['actif'])>{{ $p['nom'] }} · {{ $p['devise'] }} {{ $p['symbole'] !== $p['devise'] ? $p['symbole'] : '' }}</option>
              @endforeach
            </select>
            <input type="hidden" name="us_pays" value="1">
            <button class="bouton" type="submit">Valider</button>
          </form>
          <p class="reglages-note">Les prix s'affichent dans la devise du pays.</p>
          <p class="reglages-source">Pays deviné avec <a href="https://db-ip.com" rel="noopener" target="_blank">DB-IP</a></p>
        </section>
      @endif
    </div>
  </div>
</div>
