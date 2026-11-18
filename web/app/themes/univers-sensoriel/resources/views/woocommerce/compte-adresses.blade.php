{{-- Compte client : adresses. Données préparées par App\adresses_compte() (app/compte.php). --}}
<div class="compte compte-adresses">
  <p class="compte-intro">Adresses proposées par défaut à la commande.</p>

  <div class="adresses">
    @foreach ($adresses as $adresse)
      <section class="confirmation-carte adresse-carte">
        <h2>{!! App\icone($adresse['icone'], 22) !!} {{ $adresse['titre'] }}</h2>
        @if ($adresse['adresse'])
          <address>{!! wp_kses_post($adresse['adresse']) !!}</address>
        @else
          <p class="adresse-vide">Aucune adresse enregistrée.</p>
        @endif
        {!! $adresse['complement'] !!}
        <p class="adresse-action">
          <a class="lien-or" href="{{ esc_url($adresse['url']) }}" aria-label="{{ $adresse['adresse'] ? 'Modifier' : 'Ajouter' }} l'adresse de {{ mb_strtolower($adresse['titre']) }}">
            {{ $adresse['adresse'] ? 'Modifier' : 'Ajouter' }} {!! App\icone('fleche', 16) !!}
          </a>
        </p>
      </section>
    @endforeach
  </div>
</div>
