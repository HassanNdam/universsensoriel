@extends('layouts.app')

@section('content')
  @while (have_posts())
    @php
      the_post();
      global $product;
      $product = wc_get_product(get_the_ID());
      $infos = App\fiche($product);
      $images = array_values(array_filter(array_merge([(int) $product->get_image_id()], $product->get_gallery_image_ids())));
      // Photos des teintes (ou formats) : dans la galerie pour être montrées au choix, sans vignette en plus.
      $vignettes = count($images);
      foreach ($product->is_type('variable') ? $product->get_children() : [] as $enfant) {
        $photo = (int) get_post_thumbnail_id($enfant);
        if ($photo && ! in_array($photo, $images, true)) {
          $images[] = $photo;
        }
      }
      $surtitre = implode(' · ', array_filter([$infos['gamme'], $infos['rayon']->name ?? '']));
      $sousTitre = implode(' · ', array_filter([$infos['sous_titre'], $infos['reference'] ? 'N° ' . $infos['reference'] : '']));

      // « Complétez le rituel » : les autres produits de la même senteur (gel douche, crème, parfum).
      $rituel = [];
      $senteur = $infos['parfum'] ? $product->get_name() : preg_replace('/^(Gel douche parfumé|Crème corps parfumée)\s+/u', '', $product->get_name(), 1, $trouve);
      if ($infos['parfum'] || ! empty($trouve)) {
        foreach (get_posts(['post_type' => 'product', 'post_status' => 'publish', 's' => $senteur, 'search_columns' => ['post_title'], 'posts_per_page' => 8, 'post__not_in' => [get_the_ID()], 'fields' => 'ids']) as $id) {
          $nom = get_the_title($id);
          if (preg_match('/(^|\s)' . preg_quote($senteur, '/') . '$/iu', $nom)) {
            $rituel[] = wc_get_product($id);
          }
        }
        $rituel = array_slice($rituel, 0, 4);
      }
      $voisins = $infos['rayon'] ? App\produits_rayon($infos['rayon']->slug, 4 + count($rituel) + 1, [get_the_ID()]) : [];
      $voisins = array_slice(array_values(array_filter($voisins, fn ($p) => ! in_array($p->get_id(), array_map(fn ($r) => $r->get_id(), $rituel), true))), 0, 4);
    @endphp

    <div class="conteneur">
      @include('partials.fil')
    </div>

    <article class="fiche conteneur">
      <div class="fiche-galerie" data-galerie>
        @if ($vignettes > 1)
          <ul class="fiche-vignettes">
            @foreach (array_slice($images, 0, $vignettes) as $rang => $image)
              <li>
                <button type="button" class="{{ App\est_detoure($image) ? 'est-detoure' : '' }}" data-image="{{ $rang }}" aria-label="Voir l'image {{ $rang + 1 }}" @if ($rang === 0) aria-current="true" @endif>
                  {!! wp_get_attachment_image($image, 'thumbnail', false, ['alt' => '', 'loading' => 'lazy']) !!}
                </button>
              </li>
            @endforeach
          </ul>
        @endif
        <div class="fiche-visuels">
          @forelse ($images as $rang => $image)
            <figure class="fiche-visuel {{ App\est_detoure($image) ? 'est-detoure' : 'est-photo' }}" data-visuel="{{ $rang }}" data-media="{{ $image }}" @if ($rang > 0) hidden @endif>
              {!! App\image_produit($product, 'large', ['loading' => $rang === 0 ? 'eager' : 'lazy', 'fetchpriority' => $rang === 0 ? 'high' : 'auto', 'sizes' => '(min-width: 1000px) 46vw, 92vw', 'class' => ''], $image) !!}
            </figure>
          @empty
            <figure class="fiche-visuel est-detoure">{!! App\image_produit($product) !!}</figure>
          @endforelse
          @if ($infos['reference'])
            <span class="carte-numero carte-numero-grand"><small>N°</small>{{ $infos['reference'] }}</span>
          @endif
        </div>
      </div>

      <div class="fiche-achat">
        @if ($surtitre)
          <p class="surtitre">{{ $surtitre }}</p>
        @endif
        <h1>{{ $product->get_name() }}</h1>
        @if ($sousTitre)
          <p class="fiche-sous-titre">{{ $sousTitre }}</p>
        @endif

        <p class="fiche-prix">{!! $product->get_price_html() !!}</p>

        @if ($product->get_short_description())
          <div class="fiche-accroche">{!! wpautop(wp_kses_post($product->get_short_description())) !!}</div>
        @endif

        <div class="fiche-panier">
          @php(wc_print_notices())
          @if ($product->is_in_stock())
            <p class="fiche-stock">{!! App\icone('coche', 16) !!} {{ App\texte('produit_stock') }}</p>
          @endif
          @php(woocommerce_template_single_add_to_cart())
        </div>

        <ul class="fiche-garanties">
          @foreach (App\reassurances() as [$icone, $titre, $texte])
            <li>{!! App\icone($icone, 22) !!}<p><strong>{{ $titre }}</strong> {{ $texte }}</p></li>
          @endforeach
        </ul>

        @if ($infos['familles'] || $infos['notes'])
          <section class="profil" aria-labelledby="profil-titre">
            <h2 id="profil-titre">Profil olfactif</h2>
            <dl>
              @if ($infos['familles'])
                <div>
                  <dt>{!! App\icone('fleur', 20) !!} Famille</dt>
                  <dd>
                    @foreach ($infos['familles'] as $famille)
                      <a class="pastille" href="{{ get_term_link($famille) }}">{{ $famille->name }}</a>
                    @endforeach
                  </dd>
                </div>
              @endif
              @if ($infos['notes'])
                <div>
                  <dt>{!! App\icone('pyramide-coeur', 20) !!} Notes</dt>
                  <dd>
                    @foreach ($infos['notes'] as $note)
                      <span class="pastille pastille-note">{{ $note }}</span>
                    @endforeach
                  </dd>
                </div>
              @endif
              @if ($infos['genre'])
                <div>
                  <dt>{!! App\icone('compte', 20) !!} Pour</dt>
                  <dd>{{ $infos['genre'] }}</dd>
                </div>
              @endif
              @if ($infos['parfum'] && $infos['rayon']->slug !== 'parfums-enfant')
                <div>
                  <dt>{!! App\icone('sablier', 20) !!} Concentration</dt>
                  <dd>{{ App\texte('produit_concentration') }}</dd>
                </div>
              @endif
            </dl>
          </section>
        @endif

        <div class="accordeons">
          @if ($product->get_description())
            <details class="accordeon" open>
              <summary>Description <span class="facette-signe" aria-hidden="true"></span></summary>
              <div class="prose">{!! wpautop(wp_kses_post($product->get_description())) !!}</div>
            </details>
          @endif
          @if ($infos['parfum'] && App\texte('produit_conseils'))
            <details class="accordeon">
              <summary>Conseils d'utilisation <span class="facette-signe" aria-hidden="true"></span></summary>
              <div class="prose">
                <p>{{ App\texte('produit_conseils') }}</p>
              </div>
            </details>
          @endif
          <details class="accordeon">
            <summary>Livraison et retours <span class="facette-signe" aria-hidden="true"></span></summary>
            <div class="prose">
              <p>{{ App\texte('produit_livraison') }}</p>
              <p>{{ App\texte('produit_retours') }} <a href="{{ home_url('/livraison-et-retours/') }}">En savoir plus</a></p>
            </div>
          </details>
        </div>
      </div>
    </article>

    @php($avisProduit = App\avis(['produit' => get_the_ID()]))
    @php($avisRecu = ($_GET['avis'] ?? '') === 'recu')
    @if ($avisProduit || $avisRecu || App\peut_donner_avis($product))
      <section class="section avis-fiche" id="avis">
        <div class="conteneur">
          <header class="section-tete">
            <h2>{{ $avisProduit ? 'Avis clients' : 'Donnez votre avis' }}</h2>
          </header>
          @if ($avisRecu)
            <p class="woocommerce-message" role="status">{{ App\texte('avis_merci') }}</p>
          @endif
          @if ($avisProduit)
            @include('partials.avis-carrousel', ['liste' => $avisProduit, 'lienProduit' => false, 'titre' => 'Avis sur ce produit'])
            <p class="avis-note"><a class="lien-simple" href="{{ home_url('/avis-clients/') }}">Comment nous recueillons les avis</a></p>
          @endif
          @if (! is_user_logged_in() && $avisProduit)
            <p class="avis-note">Vous avez acheté ce produit ? <a class="lien-simple" href="{{ wc_get_page_permalink('myaccount') }}">Connectez-vous</a> pour donner votre avis.</p>
          @endif
          @if (App\peut_donner_avis($product) && ! $avisRecu)
            <div class="avis-formulaire">
              @php(comment_form([
                'title_reply' => $avisProduit ? 'Donnez votre avis' : '',
                'title_reply_before' => '<h3>', 'title_reply_after' => '</h3>',
                'comment_notes_before' => '', 'logged_in_as' => '', 'label_submit' => 'Envoyer mon avis', 'class_submit' => 'bouton',
                'comment_field' => '<p class="avis-champ"><label for="rating">Votre note</label><select name="rating" id="rating" required><option value="">Choisir…</option><option value="5">5 – Excellent</option><option value="4">4 – Très bien</option><option value="3">3 – Bien</option><option value="2">2 – Moyen</option><option value="1">1 – Décevant</option></select></p>'
                  . '<p class="avis-champ"><label for="comment">Votre avis</label><textarea id="comment" name="comment" rows="5" required></textarea></p>'
                  . '<p class="avis-mention">' . esc_html(App\texte('avis_mention')) . '</p>',
              ], get_the_ID()))
            </div>
          @endif
        </div>
      </section>
    @endif

    @if ($rituel)
      <section class="section section-lin">
        <div class="conteneur">
          <header class="section-tete">
            <h2>{{ App\texte('produit_meme_senteur') }}</h2>
          </header>
          <div class="grille-produits">
            @foreach ($rituel as $produit)
              @include('partials.carte-produit', ['produit' => $produit])
            @endforeach
          </div>
        </div>
      </section>
    @endif

    @if ($voisins)
      <section class="section">
        <div class="conteneur">
          <header class="section-tete">
            <h2>{{ App\texte('produit_voisins') }}</h2>
          </header>
          <div class="grille-produits">
            @foreach ($voisins as $produit)
              @include('partials.carte-produit', ['produit' => $produit])
            @endforeach
          </div>
          <p class="section-suite"><a class="bouton bouton-contour" href="{{ get_term_link($infos['rayon']) }}">Voir tout le rayon</a></p>
        </div>
      </section>
    @endif
  @endwhile
@endsection
