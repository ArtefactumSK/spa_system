# Project Rules – SPA

## General
- Projekt je WordPress-based systém (SPA).
- Používaj DB-first architektúru.
- Uprednostňuj stabilitu a čitateľnosť pred optimalizáciou.
- Nevymýšľaj nové koncepty bez výslovného zadania.

## Code Safety
- Neupravuj existujúce core súbory bez výslovného súhlasu.
- Nevykonávaj presuny alebo mazanie súborov.
- Nové súbory vždy vytváraj explicitne a oddelene.

## WordPress Rules
- Používaj WordPress hooks (actions, filters).
- Nepoužívaj priame SQL bez prípravy a komentárov.
- Rešpektuj child theme architektúru (Blocksy Pro).

## Database
- Databáza je primárny zdroj pravdy.
- Logika nesmie byť viazaná len na post meta.
- Každá DB zmena musí byť zdokumentovaná v /docs.

## Documentation
- Dokumentáciu píš výhradne do /docs v Markdown.
- Dokumentuj *prečo* bolo rozhodnutie prijaté, nie len *čo*.
