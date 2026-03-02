# MetaCode

MetaCode es un generador de plugins para WordPress que te permite **inyectar snippets** (HTML, CSS, JS, PHP embebido en HTML) en los hooks del Codex y **probarlos en tiempo real** desde una interfaz gráfica dentro del panel de administración.

Pensado para desarrolladores, implementadores y power users que quieren experimentar, agrupar y exportar código sin tocar directamente los archivos del tema o de otros plugins.

---

## Características principales

- 🧩 **Snippets como Custom Post Type**  
  Cada bloque de código se guarda como un _snippet_ (`mc_snippet`) editable desde el editor clásico de WordPress.

- 🪝 **Inyección por hooks del Codex**  
  Asigna cada snippet a un hook como:
  - `wp_head`
  - `wp_body_open`
  - `wp_footer`
  - `loop_start`, `loop_end`
  - `the_post`
  - `get_sidebar`, `get_footer`
  - `comment_form_before`, `comment_form_after`
  - `wp_enqueue_scripts` (uso técnico)

- ✅ **Estado Activo / Inactivo**  
  Activa o desactiva snippets sin borrarlos para poder probar distintas variantes de código.

- 📝 **Notas internas por snippet**  
  Campo de observaciones para documentar qué hace cada bloque, contexto, tickets, etc.

- 📂 **Categorías propias (Taxonomía `mc_categoria`)**  
  Agrupa snippets por proyecto, cliente o funcionalidad.  
  Cada categoría dispone de:
  - Descripción
  - **Autor** del conjunto
  - **Versión** del “plugin virtual” generado

- 👁️ **Visor de Snippets por Categoría**  
  Desde el submenú _“Análisis de Snippets”_ puedes:
  - Ver un **índice de categorías** con autor, versión y número de snippets.
  - Entrar a una categoría y visualizar el **código PHP de un plugin completo** generado a partir de esa categoría.

- 📦 **Exportación a ZIP (Factoría de Plugins)**  
  Un solo clic para descargar una categoría como plugin independiente:
  - Estructura del ZIP:
    - `<slug-de-la-categoria>/<slug-de-la-categoria>.php`
  - El archivo PHP contiene:
    - Header estándar de plugin (`Plugin Name`, `Description`, `Version`, `Author`)
    - Todos los `add_action()` necesarios para inyectar los snippets según sus hooks.

---

## Requisitos mínimos

- WordPress 5.0+ (recomendado 6.x)
- PHP 7.4+ o compatible
- Extensión `ZipArchive` habilitada en PHP (requerida para la exportación ZIP)

---

## Instalación

1. Clona o descarga este repositorio:
   ```bash
   git clone https://github.com/tu-usuario/metacode.git