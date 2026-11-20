{{-- Adresses d'une commande dans un e-mail. Données : App\mail_adresses(). --}}
<table role="presentation" class="adresses" width="100%" cellpadding="0" cellspacing="0" border="0">
  <tr>
    @foreach ($adresses as $adresse)
      <td class="adresse" width="{{ count($adresses) > 1 ? '50%' : '100%' }}" valign="top">
        <h3>{{ $adresse['titre'] }}</h3>
        <p>
          {!! wp_kses_post($adresse['adresse'] ?: 'Non renseignée') !!}
          @foreach ($adresse['lignes'] as $ligne)
            <br>{!! $ligne !!}
          @endforeach
        </p>
        {!! $adresse['ajouts'] !!}
      </td>
    @endforeach
  </tr>
</table>
