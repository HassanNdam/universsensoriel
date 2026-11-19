{{-- En-tête de tous les e-mails : ouvre le document, la carte et le corps. Le pied (mails/pied) les referme. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', get_locale()) }}">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="color-scheme" content="light only">
  <meta name="supported-color-schemes" content="light only">
  <title>{{ $titre ?: $marque }}</title>
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@400;500&amp;family=Marcellus&amp;display=swap" rel="stylesheet">
</head>
<body>
  <table role="presentation" class="fond" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td class="marge" align="center">
        <table role="presentation" class="carte" width="600" cellpadding="0" cellspacing="0" border="0">
          <tr>
            <td class="entete" align="center">
              <a href="{{ $accueil }}"><img src="{{ $logo }}" alt="{{ $marque }}" width="190" height="94"></a>
            </td>
          </tr>
          <tr>
            <td class="corps">
              @if ($titre)
                <h1>{{ $titre }}</h1>
                <table role="presentation" class="filet" align="center" width="48" cellpadding="0" cellspacing="0" border="0"><tr><td>&nbsp;</td></tr></table>
              @endif
