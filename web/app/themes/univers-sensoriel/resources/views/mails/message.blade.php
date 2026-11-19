{{-- Corps d'un e-mail rédigé par le thème : salutation, texte, note, bouton, puis détail de la commande. --}}
<div class="message">
  @if ($bonjour)
    <p>{{ $bonjour }}</p>
  @endif
  @foreach ($paragraphes as $paragraphe)
    <p>{!! $paragraphe !!}</p>
  @endforeach
  @if ($note)
    <blockquote>{!! $note !!}</blockquote>
  @endif
  {!! $complement !!}
</div>

@if ($bouton)
  <table role="presentation" class="bouton-zone" align="center" cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td class="bouton-case" align="center"><a class="bouton" href="{{ $bouton['url'] }}">{{ $bouton['libelle'] }}</a></td>
    </tr>
  </table>
@endif

{!! $detail !!}
