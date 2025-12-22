# SPA – Databázový model (DB Schema)

## 1. Účel dokumentu
Tento dokument popisuje databázový model systému SPA.
Slúži ako:
- referenčný prehľad doménových entít
- zdroj pravdy pre DB-first architektúru
- most medzi architektúrou a implementáciou

Dokument neobsahuje konkrétnu SQL implementáciu, ale **logický model**.

---

## 2. Základné princípy DB modelu

### 2.1 DB-first prístup
- Všetky kľúčové vzťahy existujú v databáze.
- WordPress post meta sa nepoužíva ako primárne úložisko.
- WP tabuľky slúžia len na:
  - používateľov
  - autentifikáciu
  - oprávnenia

---

### 2.2 Explicitné vzťahy
- Každý vzťah je reprezentovaný cudzím kľúčom (logicky).
- Neexistujú implicitné väzby „len podľa ID“.

---

## 3. Doménové entity a ich vzťahy

---

### 3.1 Tréner (Trainer)

**Charakteristika:**
- Samostatná doménová entita.
- Môže byť prepojená na WP user, ale nie je na tom závislá.

**Základné atribúty:**
- ID trénera
- meno, priezvisko
- kontaktné údaje
- stav (aktívny / neaktívny)
- voliteľné prepojenie na WP user

**Vzťahy:**
- 1 tréner → N programov
- 1 tréner → N tréningových jednotiek

---

### 3.2 Rodič (Parent)

**Charakteristika:**
- Zodpovedná osoba.
- Primárny kontaktný bod.

**Základné atribúty:**
- ID rodiča
- meno, priezvisko
- email, telefón
- stav

**Vzťahy:**
- 1 rodič → N detí

---

### 3.3 Dieťa (Child)

**Charakteristika:**
- Účastník tréningov.
- Vždy viazané na rodiča.

**Základné atribúty:**
- ID dieťaťa
- meno, priezvisko
- dátum narodenia
- stav

**Vzťahy:**
- N detí → 1 rodič
- N detí → M programov (cez väzobnú tabuľku)

---

### 3.4 Program (Training Program)

**Charakteristika:**
- Tréningový program.
- Obsahuje rozvrhové pravidlá, nie konkrétne dátumy.

**Základné atribúty:**
- ID programu
- názov
- popis
- rozvrhové pravidlo (deň, čas, periodicita)
- stav

**Vzťahy:**
- N programov → 1 tréner
- N programov → M detí
- 1 program → N tréningových jednotiek

---

### 3.5 Tréningová jednotka (Training Unit)

**Charakteristika:**
- Konkrétny termín tréningu.
- Vzniká generovaním z programu.

**Základné atribúty:**
- ID jednotky
- dátum
- čas začiatku / konca
- stav (plánovaná, prebehnutá, zrušená)

**Vzťahy:**
- N jednotiek → 1 program
- N jednotiek → 1 tréner
- 1 jednotka → N dochádzok

---

### 3.6 Dochádzka (Attendance)

**Charakteristika:**
- Manuálna evidencia účasti.
- Najjemnejšia granularita dát.

**Základné atribúty:**
- ID dochádzky
- prítomnosť (áno / nie)
- poznámka
- čas zápisu

**Vzťahy:**
- N dochádzok → 1 tréningová jednotka
- N dochádzok → 1 dieťa

---

## 4. Väzobné tabuľky (logický koncept)

### 4.1 Dieťa ↔ Program
- umožňuje:
  - viac programov pre jedno dieťa
  - jednoduché odhlásenie / prihlásenie
- obsahuje:
  - dátum priradenia
  - stav

---

## 5. Integrácia s WordPress

### 5.1 WP Users
- WP users sa používajú len na:
  - prihlásenie
  - roly
- Doménové entity nie sú WP posts.

### 5.2 Prepojenia
- Tréner a rodič môžu mať referenciu na WP user ID.
- Väzba je voliteľná a nahraditeľná.

---

## 6. Evolúcia DB modelu
- Každá zmena DB modelu:
  - musí byť zaznamenaná
  - nesmie rozbiť existujúce dáta
- Migračná logika má byť oddelená od aplikačnej.

---

## 7. Súvisiace dokumenty
- `architecture.md` – architektúra systému
- `workflows.md` – procesy a toky
- `decisions.md` – technické rozhodnutia
