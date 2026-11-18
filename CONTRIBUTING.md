# Travailler sur le dépôt

Le dépôt suit **Git flow**. Deux branches vivent en permanence, les autres sont temporaires.

| Branche | Rôle | Part de | Revient dans |
| --- | --- | --- | --- |
| `main` | Ce qui est en ligne. Chaque commit est une version, avec son étiquette `vX.Y.Z` | | |
| `develop` | La prochaine version, toujours en état de marche | | |
| `feature/<sujet>` | Une évolution (ex. `feature/tunnel-de-commande`) | `develop` | `develop` |
| `release/<version>` | Préparation d'une version : journal, derniers réglages | `develop` | `main` puis `develop` |
| `hotfix/<sujet>` | Correction urgente du site en ligne | `main` | `main` puis `develop` |

On ne committe jamais directement sur `main`. Les fusions se font avec `--no-ff` pour garder la trace de chaque branche.

## Une évolution

```bash
git checkout develop && git pull
git checkout -b feature/mon-sujet
# … commits …
git checkout develop
git merge --no-ff feature/mon-sujet
git push origin develop
git branch -d feature/mon-sujet
```

## Une version

```bash
git checkout -b release/0.2.0 develop
# mettre à jour CHANGELOG.md, puis committer
git checkout main && git merge --no-ff release/0.2.0
git tag -a v0.2.0 -m "Version 0.2.0"
git checkout develop && git merge --no-ff release/0.2.0
git push origin main develop --tags
git branch -d release/0.2.0
```

Numéros de version : `MAJEUR.MINEUR.CORRECTIF`. Tant que le site n'est pas en ligne, on reste en `0.x` ; la mise en ligne sera la `1.0.0`.

## Une correction urgente

```bash
git checkout -b hotfix/mon-sujet main
# … correction, CHANGELOG.md …
git checkout main && git merge --no-ff hotfix/mon-sujet
git tag -a v1.0.1 -m "Version 1.0.1"
git checkout develop && git merge --no-ff hotfix/mon-sujet
git push origin main develop --tags
```

## Messages de commit

En français, au format `type(périmètre): résumé` :

- `feat` une nouveauté, `fix` une correction, `style` la mise en forme, `perf` la vitesse ;
- `docs` la documentation, `chore` l'outillage, `refactor` une réécriture sans changement visible.

Périmètres courants : `theme`, `catalogue`, `seed`, `seo`, `boutique`.

Exemple : `feat(boutique): filtre par famille olfactive`.

## Avant de pousser

- `npm run build` dans le thème passe sans erreur ;
- `composer seed` se relance sans doublon ;
- ni `.env`, ni export de base, ni mot de passe dans le commit.
