{{-- Détail d'une commande dans un e-mail : articles, totaux, note. Données : App\mail_commande(). --}}
{!! $avant !!}

<table role="presentation" class="commande-titre" width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <td>
      <h2>
        @if ($commande['url'])
          <a href="{{ $commande['url'] }}">Commande n° {{ $commande['numero'] }}</a>
        @else
          Commande n° {{ $commande['numero'] }}
        @endif
      </h2>
      @if ($commande['reperes'])
        <p class="discret">{{ implode(' · ', $commande['reperes']) }}</p>
      @endif
    </td>
  </tr>
</table>

<table role="presentation" class="articles" width="100%" cellpadding="0" cellspacing="0" border="0">
  @foreach ($commande['articles'] as $article)
    <tr>
      <td class="article-image" width="64" valign="top">
        @if ($article['image'])
          <img src="{{ $article['image'] }}" alt="" width="64" height="64">
        @endif
      </td>
      <td class="article-nom" valign="top">
        @if ($article['url'])
          <a href="{{ $article['url'] }}">{{ $article['nom'] }}</a>
        @else
          {{ $article['nom'] }}
        @endif
        @if ($article['options'])
          <br><span class="discret">{{ $article['options'] }}</span>
        @endif
        @if ($article['reference'])
          <br><span class="discret">Réf. {{ $article['reference'] }}</span>
        @endif
        <br><span class="discret">Quantité : {{ $article['quantite'] }}</span>
      </td>
      <td class="article-total" align="right" valign="top">{!! $article['total'] !!}</td>
    </tr>
  @endforeach
</table>

<table role="presentation" class="totaux" width="100%" cellpadding="0" cellspacing="0" border="0">
  @foreach ($commande['totaux'] as $ligne)
    <tr class="{{ $ligne['cle'] === 'order_total' ? 'total' : 'sous-total' }}">
      <th align="left" scope="row">{{ $ligne['libelle'] }}</th>
      <td align="right">{!! $ligne['valeur'] !!}</td>
    </tr>
  @endforeach
</table>

@if ($commande['note'])
  <h3>Note de commande</h3>
  <blockquote>{!! nl2br(esc_html($commande['note'])) !!}</blockquote>
@endif

{!! $apres !!}
