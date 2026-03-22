#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""Traducción offline usando Argos Translate.

Uso:
  python3 scripts/argos_translate.py es en "Hola mundo"

Requisitos (en el servidor):
  pip install argostranslate
  # e instalar paquetes ES<->EN de Argos
"""

import sys


def main() -> int:
    if len(sys.argv) < 4:
        return 2

    src = sys.argv[1]
    dst = sys.argv[2]
    text = sys.argv[3]

    try:
        from argostranslate import translate
    except Exception:
        return 3

    try:
        t = translate.get_translation_from_codes(src, dst)
        if t is None:
            return 4
        out = t.translate(text)
        if out is None:
            return 5
        sys.stdout.write(out)
        return 0
    except Exception:
        return 6


if __name__ == "__main__":
    raise SystemExit(main())
