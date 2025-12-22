# Project Context – SPA

SPA (Samuel Piasecký Academy) je systém pre športovú akadémiu.

## Doménové pojmy
- Tréner – samostatná doménová entita (nie len WP user).
- Dieťa – registrovaný účastník tréningov.
- Rodič – zodpovedná osoba, viazaná na dieťa.
- Program – tréningový program (rozvrhové pravidlo).
- Tréningová jednotka – konkrétny dátum a čas generovaný z programu.
- Dochádzka – manuálna evidencia viazaná na tréningovú jednotku.

## Architektúra
- DB-first prístup.
- WordPress slúži ako administračné a prezentačné rozhranie.
- Logika je oddelená od UI.

## Ciele projektu
- Stabilný, dlhodobo udržateľný systém.
- Jasná architektúra bez hackov.
- Možnosť budúceho rozširovania (platby, notifikácie, reporting).
