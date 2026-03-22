FALTA COPIAR ARCHIVOS DE FLATPICKR (LOCAL)

Este proyecto fue ajustado para NO usar CDN y cargar flatpickr desde:
  public/vendor/flatpickr/

Debes colocar estos archivos (misma estructura):
  public/vendor/flatpickr/flatpickr.min.css
  public/vendor/flatpickr/flatpickr.min.js
  public/vendor/flatpickr/l10n/es.js
  public/vendor/flatpickr/plugins/monthSelect/style.css
  public/vendor/flatpickr/plugins/monthSelect/index.js

Opciones para obtenerlos:
  1) Desde node/npm (recomendado):
     npm i flatpickr
     Copiar desde node_modules/flatpickr/dist/

  2) Descargando el paquete desde el repositorio oficial y copiando dist/

NOTA: si no usas el selector de mes (monthSelect), igual puedes dejar esos 2 archivos,
pero la vista de reporte_mes los requiere.
