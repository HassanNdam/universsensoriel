{{-- Carte produit. Variables : $produit (WC_Product), $titre (balise du nom, h3 par défaut), $eager (image prioritaire). --}}
@php
  $infos = App\fiche($produit);
  $balise = $titre ?? 'h3';
  $ligne = $infos['parfum'] && $infos['familles']
    ? implode(' · ', array_map(fn ($f) => $f->name, array_slice($infos['familles'], 0, 2)))
    : ($infos['parfum'] ? '' : ($infos['sous_titre'] ?: ($infos['rayon']->name ?? '')));
  $surtitre = $infos['parfum'] ? trim('Extrait de parfum ' . mb_strtolower($infos['genre'] === 'Mixte' ? '' : $infos['genre'])) : ($infos['rayon']->name ?? '');
  $simple = $produit->is_type('simple') && $produit->is_purchasable() && $produit->is_in_stock();
@endphp

<article class="carte">
  <a class="carte-visuel" href="{{ $produit->get_permalink() }}" tabindex="-1" aria-hidden="true">
    {!! App\image_produit($produit, 'medium', ['loading' => ! empty($eager) ? 'eager' : 'lazy', 'sizes' => '(min-width: 1100px) 300px, (min-width: 700px) 33vw, 50vw']) !!}
    @if ($infos['reference'])
      <span class="carte-numero"><small>N°</small>{{ $infos['reference'] }}</span>
    @endif
    @if ($produit->is_on_sale())
      <span class="carte-badge">Offre</span>
    @elseif (! $produit->is_in_stock())
      <span class="carte-badge carte-badge-neutre">Bientôt de retour</span>
    @endif
  </a>
  <div class="carte-corps">
    @if ($surtitre)
      <p class="carte-surtitre">{{ $surtitre }}</p>
    @endif
    <{{ $balise }} class="carte-nom"><a href="{{ $produit->get_permalink() }}">{{ $produit->get_name() }}</a></{{ $balise }}>
    @if ($ligne && $ligne !== $surtitre)
      <p class="carte-ligne">{{ $ligne }}</p>
    @endif
    <p class="carte-prix">{!! App\prix_carte($produit) !!}</p>
  </div>
  @if ($simple)
    <a class="carte-action add_to_cart_button ajax_add_to_cart" href="{{ $produit->add_to_cart_url() }}" data-product_id="{{ $produit->get_id() }}" data-quantity="1" rel="nofollow" aria-label="Ajouter {{ $produit->get_name() }} au panier">Ajouter au panier</a>
  @else
    <a class="carte-action" href="{{ $produit->get_permalink() }}" aria-label="Choisir le format de {{ $produit->get_name() }}">{{ $produit->is_in_stock() ? 'Choisir le format' : 'Voir la fiche' }}</a>
  @endif
</article>
