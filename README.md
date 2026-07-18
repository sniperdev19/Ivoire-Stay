# Afristay

SaaS B2B de gestion hôtelière + vitrine B2C, en PHP (front controller) avec Tailwind CSS et Alpine.js.

## CSS Tailwind (build)

Le CSS n'est plus servi par le CDN : il est **compilé** via la CLI autonome
(`tailwindcss.exe`, non versionnée — voir `.gitignore`).

Il y a **deux feuilles**, une par espace, qui contiennent chacune Tailwind
(base + utilitaires) **+** les styles composant de l'espace. Ces derniers sont
placés en `@layer components` pour que les utilitaires Tailwind (`bg-…`, `text-…`)
puissent toujours les surcharger.

| Entrée                   | Sortie                            | Chargée par         |
| ------------------------- | --------------------------------- | -------------------- |
| `src/vitrine.input.css` | `public/assets/css/vitrine.css` | layout vitrine + 404 |
| `src/saas.input.css`    | `public/assets/css/saas.css`    | layout saas          |

Les classes sont détectées dans `src/templates/**/*.php` et `public/assets/js/**/*.js`
(voir `tailwind.config.js`).

**Recompiler après avoir ajouté/modifié des classes Tailwind ou un style composant :**

```bash
./tailwindcss.exe -i src/vitrine.input.css -o public/assets/css/tailwind.css --minify      
./tailwindcss.exe -i src/saas.input.css -o public/assets/css/tailwind.css --minify
```

> ⚠️ Si une classe Tailwind n'apparaît pas dans un fichier scanné (ex. classe construite
> dynamiquement en JS par concaténation), elle sera absente du build. Dans ce cas, écrire
> la classe e toutes lettres dans le template/JS, ou l'ajouter à `safelist` dans `tailwind.config.js`.

> `login`/`register` n'utilisent pas Tailwind (CSS dédié dans `css/pages/`), donc ne chargent aucune de ces feuilles.

> Si `tailwindcss.exe` est absent, le retélécharger :
> `curl -sL -o tailwindcss.exe https://github.com/tailwindlabs/tailwindcss/releases/download/v3.4.17/tailwindcss-windows-x64.exe`

## Organisation des assets

```
public/assets/
├── css/  vitrine.css, saas.css, pages/*.css   (vitrine.css/saas.css = Tailwind + composants compilés)
└── js/   vitrine.js, saas.js, pages/*.js
```
