{{-- Un avis client, reproduit tel quel. Variables : $avis (voir App\avis()), $lienProduit (bool). --}}
<figure class="avis">
  <blockquote>
    <p>{{ $avis['extrait'] ? '« ' . rtrim($avis['texte']) . ' […] »' : '« ' . $avis['texte'] . ' »' }}</p>
  </blockquote>
  <figcaption>
    {!! App\etoiles($avis['note']) !!}
    <p class="avis-auteur">
      <strong>{{ $avis['auteur'] }}</strong>
      <span>{{ $avis['date_lisible'] }}</span>
    </p>
    @if ($avis['verifie'])
      <p class="avis-badge">{!! App\icone('coche', 14) !!} Achat vérifié</p>
    @endif
    @if ($avis['extrait'])
      <p class="avis-mention">Extrait</p>
    @endif
    @if (! empty($lienProduit) && $avis['produit'])
      <a class="avis-produit" href="{{ $avis['produit']->get_permalink() }}">
        <span class="avis-vignette">{!! App\image_produit($avis['produit'], 'thumbnail', ['alt' => '']) !!}</span>
        <span>À propos de <strong>{{ $avis['produit']->get_name() }}</strong></span>
      </a>
    @elseif (! empty($lienProduit))
      <p class="avis-produit avis-produit-boutique">{!! App\icone('boutique', 20) !!} <span>À propos de la boutique</span></p>
    @endif
  </figcaption>
</figure>
