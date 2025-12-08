#!/bin/bash
#
# clean-scaffolding.sh
# Limpia el contenido de ejemplo de PatternLab para preparar el proyecto base para forks
#
# Uso: npm run clean:scaffolding
#      o directamente: bash dev/scripts/clean-scaffolding.sh
#
# IMPORTANTE: Este script solo elimina archivos que pertenecen al scaffolding
# original de Talampaya. Los archivos creados por el fork son preservados.
#

# No usar set -e porque interfiere con el flujo interactivo
# set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color
BOLD='\033[1m'
DIM='\033[2m'

# Directorio raíz del proyecto (relativo al script)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"

# Directorios de PatternLab
PATTERNLAB_PATTERNS="$PROJECT_ROOT/patternlab/source/_patterns"
PATTERNLAB_CSS="$PROJECT_ROOT/patternlab/source/css/scss"
PATTERNLAB_DATA="$PROJECT_ROOT/patternlab/source/_data"
PATTERNLAB_STYLE="$PROJECT_ROOT/patternlab/source/css/style.scss"
THEME_VIEWS="$PROJECT_ROOT/src/theme/views"

# Contadores globales
DELETED_COUNT=0
SKIPPED_COUNT=0
MODIFIED_COUNT=0

# Arrays para tracking
declare -a DELETED_FILES
declare -a SKIPPED_FILES
declare -a MODIFIED_FILES

# Arrays para análisis inicial - solo archivos BASE de Talampaya
declare -a BASE_DIRS_TO_PROCESS=()
declare -a FORK_DIRS_FOUND=()
declare -a MODIFIED_DIRS=()
declare -a UNMODIFIED_DIRS=()
declare -a MISSING_DIRS=()
declare -a BASE_SCSS_FILES=()
declare -a FORK_SCSS_FILES=()
declare -a MODIFIED_SCSS=()
declare -a UNMODIFIED_SCSS=()
declare -a BASE_VIEW_FILES=()
declare -a FORK_VIEW_FILES=()
declare -a MODIFIED_VIEWS=()
declare -a UNMODIFIED_VIEWS=()
declare -a BASE_COMPONENT_FILES=()
declare -a FORK_COMPONENT_FILES=()
declare -a MODIFIED_COMPONENTS=()
declare -a UNMODIFIED_COMPONENTS=()

# Theme scaffolding files
declare -a THEME_SCAFFOLDING_FILES=()
declare -a THEME_SCAFFOLDING_DIRS=()
declare -a MODIFIED_THEME_SCAFFOLDING=()
declare -a UNMODIFIED_THEME_SCAFFOLDING=()

# Flags
AUTO_YES=false
DRY_RUN=false
VERBOSE=false

# Resultado de la acción principal
MAIN_ACTION=""

#######################################
# LISTA DE ARCHIVOS BASE DE TALAMPAYA
# Solo estos ARCHIVOS serán considerados para eliminación
# Cualquier otro archivo es del fork y será preservado
# IMPORTANTE: Se listan archivos individuales, NO directorios
#######################################

# Archivos de PatternLab que pertenecen al scaffolding base
# Formato: ruta relativa a _patterns/
BASE_PATTERNLAB_FILES=(
    # Root level md files
    "atoms/_atoms.md"
    "molecules/_molecules.md"
    "organisms/_organisms.md"
    "templates/_templates.md"
    "pages/_pages.md"
    "macros/_macros.md"
    "macros/forms.twig"

    # Atoms - buttons
    "atoms/buttons/_buttons.md"
    "atoms/buttons/buttons.md"
    "atoms/buttons/buttons.twig"

    # Atoms - forms
    "atoms/forms/_forms.md"
    "atoms/forms/checkbox.md"
    "atoms/forms/checkbox.twig"
    "atoms/forms/html5-inputs.md"
    "atoms/forms/html5-inputs.twig"
    "atoms/forms/radio-buttons.md"
    "atoms/forms/radio-buttons.twig"
    "atoms/forms/select-menu.md"
    "atoms/forms/select-menu.twig"
    "atoms/forms/text-fields.md"
    "atoms/forms/text-fields.twig"

    # Atoms - global
    "atoms/global/_global.md"
    "atoms/global/animations.md"
    "atoms/global/animations.twig"
    "atoms/global/colors.md"
    "atoms/global/colors.twig"
    "atoms/global/fonts.md"
    "atoms/global/fonts.twig"
    "atoms/global/visibility.md"
    "atoms/global/visibility.twig"

    # Atoms - images
    "atoms/images/_images.md"
    "atoms/images/avatar.md"
    "atoms/images/avatar.twig"
    "atoms/images/favicon.md"
    "atoms/images/favicon.twig"
    "atoms/images/icons.md"
    "atoms/images/icons.twig"
    "atoms/images/image.md"
    "atoms/images/image.twig"
    "atoms/images/loading-icon.md"
    "atoms/images/loading-icon.twig"
    "atoms/images/logo.md"
    "atoms/images/logo.twig"

    # Atoms - lists
    "atoms/lists/_lists.md"
    "atoms/lists/definition.md"
    "atoms/lists/definition.twig"
    "atoms/lists/ordered.md"
    "atoms/lists/ordered.twig"
    "atoms/lists/unordered.md"
    "atoms/lists/unordered.twig"

    # Atoms - media
    "atoms/media/_media.md"
    "atoms/media/audio.md"
    "atoms/media/audio.twig"
    "atoms/media/video.md"
    "atoms/media/video.twig"

    # Atoms - tables
    "atoms/tables/_tables.md"
    "atoms/tables/table.md"
    "atoms/tables/table.twig"

    # Atoms - text
    "atoms/text/_text.md"
    "atoms/text/blockquote.md"
    "atoms/text/blockquote.twig"
    "atoms/text/headings.md"
    "atoms/text/headings.twig"
    "atoms/text/hr.md"
    "atoms/text/hr.twig"
    "atoms/text/inline-elements.md"
    "atoms/text/inline-elements.twig"
    "atoms/text/paragraph.md"
    "atoms/text/paragraph.twig"
    "atoms/text/preformatted-text.md"
    "atoms/text/preformatted-text.twig"
    "atoms/text/time.md"
    "atoms/text/time.twig"

    # Molecules - blocks
    "molecules/blocks/_blocks.md"
    "molecules/blocks/block-headline-byline.md"
    "molecules/blocks/block-headline-byline.twig"
    "molecules/blocks/block-headline.md"
    "molecules/blocks/block-headline.twig"
    "molecules/blocks/block-hero.md"
    "molecules/blocks/block-hero.twig"
    "molecules/blocks/block-inset.md"
    "molecules/blocks/block-inset.twig"
    "molecules/blocks/block-thumb-headline.md"
    "molecules/blocks/block-thumb-headline.twig"
    "molecules/blocks/media-block.md"
    "molecules/blocks/media-block.twig"

    # Molecules - components
    "molecules/components/_components.md"
    "molecules/components/accordion.md"
    "molecules/components/accordion.twig"
    "molecules/components/single-comment.md"
    "molecules/components/single-comment.twig"
    "molecules/components/social-share.md"
    "molecules/components/social-share.twig"

    # Molecules - forms
    "molecules/forms/_forms.md"
    "molecules/forms/comment-form.md"
    "molecules/forms/comment-form.twig"
    "molecules/forms/newsletter.md"
    "molecules/forms/newsletter.twig"
    "molecules/forms/search.md"
    "molecules/forms/search.twig"

    # Molecules - layout
    "molecules/layout/_layout.md"
    "molecules/layout/four-up.md"
    "molecules/layout/four-up.twig"
    "molecules/layout/one-up.md"
    "molecules/layout/one-up.twig"
    "molecules/layout/three-up.md"
    "molecules/layout/three-up.twig"
    "molecules/layout/two-up.md"
    "molecules/layout/two-up.twig"

    # Molecules - media
    "molecules/media/_media.md"
    "molecules/media/figure-with-caption.twig"

    # Molecules - messaging
    "molecules/messaging/_messaging.md"
    "molecules/messaging/alert.twig"

    # Molecules - navigation
    "molecules/navigation/_navigation.md"
    "molecules/navigation/breadcrumbs.md"
    "molecules/navigation/breadcrumbs.twig"
    "molecules/navigation/footer-nav.md"
    "molecules/navigation/footer-nav.twig"
    "molecules/navigation/pagination.md"
    "molecules/navigation/pagination.twig"
    "molecules/navigation/primary-nav.md"
    "molecules/navigation/primary-nav.twig"
    "molecules/navigation/tabs.md"
    "molecules/navigation/tabs.twig"

    # Molecules - text
    "molecules/text/_text.md"
    "molecules/text/address.md"
    "molecules/text/address.twig"
    "molecules/text/blockquote-with-citation.md"
    "molecules/text/blockquote-with-citation.twig"
    "molecules/text/byline.md"
    "molecules/text/byline.twig"
    "molecules/text/heading-group.md"
    "molecules/text/heading-group.twig"
    "molecules/text/intro-text.md"
    "molecules/text/intro-text.twig"

    # Organisms - article
    "organisms/article/_article.md"
    "organisms/article/article-body.twig"

    # Organisms - comments
    "organisms/comments/_comments.md"
    "organisms/comments/comment-thread.twig"

    # Organisms - global
    "organisms/global/_global.md"
    "organisms/global/footer.md"
    "organisms/global/footer.twig"
    "organisms/global/header.md"
    "organisms/global/header.twig"

    # Organisms - sections
    "organisms/sections/_sections.md"
    "organisms/sections/latest-posts.md"
    "organisms/sections/latest-posts.twig"
    "organisms/sections/recent-tweets.md"
    "organisms/sections/recent-tweets.twig"
    "organisms/sections/related-posts.md"
    "organisms/sections/related-posts.twig"

    # Templates
    "templates/404.twig"
    "templates/article-2col.md"
    "templates/article-2col.twig"
    "templates/article.md"
    "templates/article.twig"
    "templates/blog.md"
    "templates/blog.twig"
    "templates/homepage.md"
    "templates/homepage.twig"
    "templates/layouts/page-1col.md"
    "templates/layouts/page-1col.twig"
    "templates/layouts/page-2col.md"
    "templates/layouts/page-2col.twig"
    "templates/layouts/site.twig"

    # Pages
    "pages/404.json"
    "pages/404.twig"
    "pages/about.json"
    "pages/about.twig"
    "pages/article.json"
    "pages/article.md"
    "pages/article.twig"
    "pages/blog.json"
    "pages/blog.md"
    "pages/blog~search-results.json"
    "pages/blog~search-results.md"
    "pages/blog.twig"
    "pages/contact.json"
    "pages/contact.twig"
    "pages/homepage~emergency.json"
    "pages/homepage.json"
    "pages/homepage.md"
    "pages/homepage.twig"
)

# Archivos SCSS base (relativos a scss/)
BASE_SCSS_OBJECTS=(
    "objects/_accordion.scss"
    "objects/_article.scss"
    "objects/_blocks.scss"
    "objects/_buttons.scss"
    "objects/_carousels.scss"
    "objects/_comments.scss"
    "objects/_footer.scss"
    "objects/_header.scss"
    "objects/_icons.scss"
    "objects/_layout.scss"
    "objects/_lists.scss"
    "objects/_messaging.scss"
    "objects/_nav.scss"
    "objects/_sections.scss"
    "objects/_tabs.scss"
    "objects/_text.scss"
    "objects/_tooltip.scss"
)

BASE_SCSS_BASE=(
    "base/_animation.scss"
    "base/_forms.scss"
    "base/_global-classes.scss"
    "base/_headings.scss"
    "base/_links.scss"
    "base/_lists.scss"
    "base/_media.scss"
    "base/_tables.scss"
    "base/_text.scss"
)

# Views base (relativos a views/pages/)
BASE_VIEW_PAGES=(
    "404.twig"
    "archive.twig"
    "author.twig"
    "front-page.twig"
    "index.twig"
    "page.twig"
    "search.twig"
    "single.twig"
)

# Components base (relativos a views/components/)
BASE_COMPONENTS=(
    "comment.twig"
    "pagination.twig"
    "post-teaser.twig"
)

#######################################
# Muestra el banner inicial
#######################################
show_banner() {
    echo ""
    echo -e "${CYAN}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║${NC}  ${BOLD}Talampaya - Clean Scaffolding${NC}                               ${CYAN}║${NC}"
    echo -e "${CYAN}║${NC}  Limpia contenido BASE de ejemplo, preserva archivos fork   ${CYAN}║${NC}"
    echo -e "${CYAN}╚══════════════════════════════════════════════════════════════╝${NC}"
    echo ""
}

#######################################
# Muestra ayuda
#######################################
show_help() {
    echo "Uso: $0 [opciones]"
    echo ""
    echo "Opciones:"
    echo "  -h, --help      Muestra esta ayuda"
    echo "  -y, --yes       Modo no interactivo (elimina todo el scaffolding base sin preguntar)"
    echo "  -d, --dry-run   Muestra qué se eliminaría sin hacer cambios"
    echo "  -v, --verbose   Muestra más detalles (lista archivos del fork preservados)"
    echo ""
    echo "IMPORTANTE: Este script SOLO elimina archivos del scaffolding BASE de Talampaya."
    echo "            Los archivos creados por el fork son PRESERVADOS automáticamente."
    echo ""
    echo "Archivos BASE que se eliminan (si existen):"
    echo "  - PatternLab: atoms, molecules, organisms, templates, pages definidos en BASE_PATTERNLAB_DIRS"
    echo "  - SCSS: archivos definidos en BASE_SCSS_OBJECTS y BASE_SCSS_BASE"
    echo "  - Views: archivos definidos en BASE_VIEW_PAGES"
    echo "  - Components: archivos definidos en BASE_COMPONENTS"
    echo "  - Theme scaffolding:"
    echo "      - ProjectPostType, EpicTaxonomy"
    echo "      - Models (ProjectPost, EpicTaxonomy)"
    echo "      - ACF Fields para ProjectPost"
    echo "      - Import system (ProjectImport, ImportPagesSettings)"
    echo "      - CustomPermalinks (project_post)"
    echo "      - LegalPagesGenerator y contenido HTML"
    echo "      - Example endpoint y modifier"
    echo "      - Geolocation integration"
    echo ""
    echo "Archivos del FORK (preservados):"
    echo "  - Cualquier directorio en PatternLab que NO esté en BASE_PATTERNLAB_DIRS"
    echo "  - Cualquier archivo SCSS que NO esté en BASE_SCSS_OBJECTS o BASE_SCSS_BASE"
    echo "  - Cualquier view que NO esté en BASE_VIEW_PAGES"
    echo "  - Cualquier component que NO esté en BASE_COMPONENTS"
    echo ""
}

#######################################
# Verifica si una ruta pertenece al scaffolding base de Talampaya
# Arguments:
#   $1 - Ruta del archivo (absoluta)
#   $2 - Tipo: "patternlab", "scss", "view", "component"
# Returns:
#   0 si es parte del base, 1 si no (es del fork)
#######################################
is_base_scaffolding() {
    local path="$1"
    local type="$2"
    local relative_path

    case "$type" in
        "patternlab")
            relative_path="${path#$PATTERNLAB_PATTERNS/}"
            for base_file in "${BASE_PATTERNLAB_FILES[@]}"; do
                if [[ "$relative_path" == "$base_file" ]]; then
                    return 0
                fi
            done
            return 1
            ;;
        "scss")
            relative_path="${path#$PATTERNLAB_CSS/}"
            for base_file in "${BASE_SCSS_OBJECTS[@]}" "${BASE_SCSS_BASE[@]}"; do
                if [[ "$relative_path" == "$base_file" ]]; then
                    return 0
                fi
            done
            return 1
            ;;
        "view")
            local filename
            filename=$(basename "$path")
            for base_view in "${BASE_VIEW_PAGES[@]}"; do
                if [[ "$filename" == "$base_view" ]]; then
                    return 0
                fi
            done
            return 1
            ;;
        "component")
            local filename
            filename=$(basename "$path")
            for base_comp in "${BASE_COMPONENTS[@]}"; do
                if [[ "$filename" == "$base_comp" ]]; then
                    return 0
                fi
            done
            return 1
            ;;
    esac
    return 1
}

#######################################
# Verifica si un archivo/directorio fue modificado comparando con git
# Arguments:
#   $1 - Ruta del archivo o directorio
# Returns:
#   0 si fue modificado, 1 si no
#######################################
is_modified() {
    local path="$1"
    local relative_path="${path#$PROJECT_ROOT/}"

    # Si es un directorio, verificar si algún archivo dentro fue modificado
    if [[ -d "$path" ]]; then
        local modified_files
        modified_files=$(git -C "$PROJECT_ROOT" status --porcelain "$relative_path" 2>/dev/null | wc -l)
        [[ $modified_files -gt 0 ]] && return 0
        return 1
    fi

    # Para archivos individuales
    if ! git -C "$PROJECT_ROOT" ls-files --error-unmatch "$relative_path" &>/dev/null; then
        # Archivo no está trackeado = nuevo = modificado
        return 0
    fi

    # Verificar si tiene cambios
    if git -C "$PROJECT_ROOT" diff --quiet "$relative_path" 2>/dev/null; then
        return 1 # No modificado
    else
        return 0 # Modificado
    fi
}

#######################################
# Analiza todo el proyecto y clasifica archivos
# IMPORTANTE: Solo los archivos que están en las listas BASE_* serán
# considerados para eliminación. Archivos del fork son ignorados.
#######################################
analyze_project() {
    echo -e "${BOLD}Analizando proyecto...${NC}"
    echo ""

    # Analizar ARCHIVOS de PatternLab (no directorios)
    # Buscamos todos los archivos y verificamos si están en la lista base
    while IFS= read -r file; do
        [[ -z "$file" ]] && continue

        if is_base_scaffolding "$file" "patternlab"; then
            BASE_DIRS_TO_PROCESS+=("$file")
            if is_modified "$file"; then
                MODIFIED_DIRS+=("$file")
            else
                UNMODIFIED_DIRS+=("$file")
            fi
        else
            # Es un archivo del fork - ignorar
            FORK_DIRS_FOUND+=("$file")
        fi
    done < <(find "$PATTERNLAB_PATTERNS" -type f \( -name "*.twig" -o -name "*.json" -o -name "*.md" -o -name "*.scss" -o -name "*.js" \) 2>/dev/null | sort)

    # Analizar archivos SCSS - solo los que están en la lista base
    local scss_dirs=("$PATTERNLAB_CSS/objects" "$PATTERNLAB_CSS/base")
    for scss_dir in "${scss_dirs[@]}"; do
        [[ ! -d "$scss_dir" ]] && continue

        while IFS= read -r file; do
            [[ -z "$file" ]] && continue
            [[ "$(basename "$file")" == "_main.scss" ]] && continue  # Ignorar _main.scss

            if is_base_scaffolding "$file" "scss"; then
                BASE_SCSS_FILES+=("$file")
                if is_modified "$file"; then
                    MODIFIED_SCSS+=("$file")
                else
                    UNMODIFIED_SCSS+=("$file")
                fi
            else
                # Es un archivo SCSS del fork - ignorar
                FORK_SCSS_FILES+=("$file")
            fi
        done < <(find "$scss_dir" -type f -name "*.scss" 2>/dev/null | sort)
    done

    # Analizar views/pages - solo los que están en la lista base
    local views_pages="$THEME_VIEWS/pages"
    if [[ -d "$views_pages" ]]; then
        for twig_file in "$views_pages"/*.twig; do
            [[ ! -f "$twig_file" ]] && continue

            if is_base_scaffolding "$twig_file" "view"; then
                # Solo archivos base que incluyen PatternLab
                if grep -qE '@templates|@organisms|@molecules|@atoms' "$twig_file" 2>/dev/null; then
                    BASE_VIEW_FILES+=("$twig_file")
                    if is_modified "$twig_file"; then
                        MODIFIED_VIEWS+=("$twig_file")
                    else
                        UNMODIFIED_VIEWS+=("$twig_file")
                    fi
                fi
            else
                # Es un view del fork - ignorar
                FORK_VIEW_FILES+=("$twig_file")
            fi
        done
    fi

    # Analizar views/components - solo los que están en la lista base
    local views_components="$THEME_VIEWS/components"
    if [[ -d "$views_components" ]]; then
        for twig_file in "$views_components"/*.twig; do
            [[ ! -f "$twig_file" ]] && continue

            if is_base_scaffolding "$twig_file" "component"; then
                BASE_COMPONENT_FILES+=("$twig_file")
                if is_modified "$twig_file"; then
                    MODIFIED_COMPONENTS+=("$twig_file")
                else
                    UNMODIFIED_COMPONENTS+=("$twig_file")
                fi
            else
                # Es un component del fork - ignorar
                FORK_COMPONENT_FILES+=("$twig_file")
            fi
        done
    fi

    # Analizar theme scaffolding (archivos de ejemplo del theme)
    # Estos son archivos específicos que sabemos que son del scaffolding base
    local theme_src="$PROJECT_ROOT/src/theme/src"
    local theme_assets="$PROJECT_ROOT/src/theme/assets"
    local theme_blocks="$PROJECT_ROOT/src/theme/blocks"

    # Archivos individuales de scaffolding (lista fija)
    local scaffolding_files=(
        # PostType y Taxonomy de ejemplo
        "$theme_src/Register/PostType/ProjectPostType.php"
        "$theme_src/Register/Taxonomy/EpicTaxonomy.php"
        # Models de ejemplo
        "$theme_src/Inc/Models/ProjectPost.php"
        "$theme_src/Inc/Models/EpicTaxonomy.php"
        # ContentGenerator de ejemplo
        "$theme_src/Features/ContentGenerator/Generators/ProjectPostGenerator.php"
        "$theme_src/Features/ContentGenerator/Generators/LegalPagesGenerator.php"
        # ACF Fields de ejemplo
        "$theme_src/Features/Acf/Fields/ProjectPost/ProjectPostFields.php"
        "$theme_src/Features/Acf/Fields/ProjectPost/project_post_admin_columns.php"
        # Import de ejemplo
        "$theme_src/Features/Import/ProjectImport.php"
        "$theme_src/Inc/Services/ProjectImportService.php"
        "$theme_src/Features/Admin/Pages/ImportPagesSettings.php"
        "$theme_src/Mockups/projects.csv"
        # Permalinks de ejemplo (100% para project_post)
        "$theme_src/Features/Permalinks/CustomPermalinks.php"
        # Endpoints y modifiers de ejemplo
        "$theme_src/Core/Endpoints/Custom/example-endpoint.php"
        "$theme_src/Features/Acf/Blocks/Modifiers/example-modifier.php"
        # Geolocation
        "$theme_src/Features/Admin/Pages/GeolocationSettings.php"
        "$theme_src/Features/Acf/Blocks/Modifiers/geolocation-modifier.php"
        "$theme_src/Core/Endpoints/GeolocationEndpoint.php"
        "$theme_assets/scripts/modules/geolocation.js"
    )

    for file in "${scaffolding_files[@]}"; do
        if [[ -f "$file" ]]; then
            THEME_SCAFFOLDING_FILES+=("$file")
            if is_modified "$file"; then
                MODIFIED_THEME_SCAFFOLDING+=("$file")
            else
                UNMODIFIED_THEME_SCAFFOLDING+=("$file")
            fi
        fi
    done

    # Directorios de scaffolding (lista fija)
    local scaffolding_dirs=(
        "$theme_blocks/example"
        "$theme_src/Integrations/Geolocation"
        "$theme_src/Features/Acf/Fields/ProjectPost"
        "$theme_src/Features/DefaultContent"
    )

    for dir in "${scaffolding_dirs[@]}"; do
        if [[ -d "$dir" ]]; then
            THEME_SCAFFOLDING_DIRS+=("$dir")
            if is_modified "$dir"; then
                MODIFIED_THEME_SCAFFOLDING+=("$dir")
            else
                UNMODIFIED_THEME_SCAFFOLDING+=("$dir")
            fi
        fi
    done
}

#######################################
# Muestra el resumen del análisis
#######################################
show_analysis_summary() {
    echo -e "${BOLD}${CYAN}══════════════════════════════════════════${NC}"
    echo -e "${BOLD}${CYAN}  Resumen del Análisis${NC}"
    echo -e "${BOLD}${CYAN}══════════════════════════════════════════${NC}"
    echo ""

    # Calcular totales de archivos del fork (protegidos)
    local total_fork=$((${#FORK_DIRS_FOUND[@]} + ${#FORK_SCSS_FILES[@]} + ${#FORK_VIEW_FILES[@]} + ${#FORK_COMPONENT_FILES[@]}))

    if [[ $total_fork -gt 0 ]]; then
        echo -e "${GREEN}${BOLD}Archivos del FORK detectados (protegidos):${NC}"
        echo -e "  ${GREEN}Total: $total_fork archivos/directorios NO serán eliminados${NC}"
        if [[ ${#FORK_DIRS_FOUND[@]} -gt 0 ]]; then
            echo -e "  ${DIM}PatternLab: ${#FORK_DIRS_FOUND[@]} directorios${NC}"
        fi
        if [[ ${#FORK_SCSS_FILES[@]} -gt 0 ]]; then
            echo -e "  ${DIM}SCSS: ${#FORK_SCSS_FILES[@]} archivos${NC}"
        fi
        if [[ ${#FORK_VIEW_FILES[@]} -gt 0 ]]; then
            echo -e "  ${DIM}Views: ${#FORK_VIEW_FILES[@]} archivos${NC}"
        fi
        if [[ ${#FORK_COMPONENT_FILES[@]} -gt 0 ]]; then
            echo -e "  ${DIM}Components: ${#FORK_COMPONENT_FILES[@]} archivos${NC}"
        fi
        echo ""
    fi

    echo -e "${BOLD}${BLUE}Scaffolding BASE de Talampaya (candidatos a eliminar):${NC}"
    echo ""

    # PatternLab
    echo -e "${BOLD}PatternLab Patterns (base):${NC}"
    echo -e "  Total:       ${#BASE_DIRS_TO_PROCESS[@]} elementos"
    echo -e "  ${GREEN}Sin cambios: ${#UNMODIFIED_DIRS[@]}${NC}"
    echo -e "  ${YELLOW}Modificados: ${#MODIFIED_DIRS[@]}${NC}"
    if [[ ${#MISSING_DIRS[@]} -gt 0 ]]; then
        echo -e "  ${DIM}No existen:  ${#MISSING_DIRS[@]} (${MISSING_DIRS[*]})${NC}"
    fi
    echo ""

    # SCSS
    echo -e "${BOLD}Estilos SCSS (base):${NC}"
    echo -e "  Total:       ${#BASE_SCSS_FILES[@]} archivos"
    echo -e "  ${GREEN}Sin cambios: ${#UNMODIFIED_SCSS[@]}${NC}"
    echo -e "  ${YELLOW}Modificados: ${#MODIFIED_SCSS[@]}${NC}"
    echo ""

    # Views
    echo -e "${BOLD}Theme Views (base):${NC}"
    echo -e "  Total:       ${#BASE_VIEW_FILES[@]} archivos"
    echo -e "  ${GREEN}Sin cambios: ${#UNMODIFIED_VIEWS[@]}${NC}"
    echo -e "  ${YELLOW}Modificados: ${#MODIFIED_VIEWS[@]}${NC}"
    echo ""

    # Components
    echo -e "${BOLD}Theme Components (base):${NC}"
    echo -e "  Total:       ${#BASE_COMPONENT_FILES[@]} archivos"
    echo -e "  ${GREEN}Sin cambios: ${#UNMODIFIED_COMPONENTS[@]}${NC}"
    echo -e "  ${YELLOW}Modificados: ${#MODIFIED_COMPONENTS[@]}${NC}"
    echo ""

    # Theme Scaffolding
    local total_scaffolding=$((${#THEME_SCAFFOLDING_FILES[@]} + ${#THEME_SCAFFOLDING_DIRS[@]}))
    if [[ $total_scaffolding -gt 0 ]]; then
        echo -e "${BOLD}Theme Scaffolding (ejemplos):${NC}"
        echo -e "  Total:       $total_scaffolding elementos"
        echo -e "  ${GREEN}Sin cambios: ${#UNMODIFIED_THEME_SCAFFOLDING[@]}${NC}"
        echo -e "  ${YELLOW}Modificados: ${#MODIFIED_THEME_SCAFFOLDING[@]}${NC}"
        echo -e "  ${DIM}(PostType, Taxonomy, Models, ACF Fields, Import, Permalinks, Legales, Examples, Geolocation)${NC}"
        echo ""
    fi

    # Mostrar lista de archivos del fork si verbose
    if [[ "$VERBOSE" == "true" && $total_fork -gt 0 ]]; then
        echo -e "${GREEN}Archivos del fork (protegidos):${NC}"
        for item in "${FORK_DIRS_FOUND[@]}"; do
            echo -e "  ${GREEN}✓${NC} ${item#$PROJECT_ROOT/}"
        done
        for item in "${FORK_SCSS_FILES[@]}"; do
            echo -e "  ${GREEN}✓${NC} ${item#$PROJECT_ROOT/}"
        done
        for item in "${FORK_VIEW_FILES[@]}"; do
            echo -e "  ${GREEN}✓${NC} ${item#$PROJECT_ROOT/}"
        done
        for item in "${FORK_COMPONENT_FILES[@]}"; do
            echo -e "  ${GREEN}✓${NC} ${item#$PROJECT_ROOT/}"
        done
        echo ""
    fi

    # Mostrar lista de modificados si hay
    if [[ ${#MODIFIED_DIRS[@]} -gt 0 ]]; then
        echo -e "${YELLOW}Elementos BASE modificados en PatternLab:${NC}"
        for item in "${MODIFIED_DIRS[@]}"; do
            echo -e "  ${YELLOW}→${NC} ${item#$PROJECT_ROOT/}"
        done
        echo ""
    fi

    if [[ ${#MODIFIED_SCSS[@]} -gt 0 ]]; then
        echo -e "${YELLOW}Archivos SCSS BASE modificados:${NC}"
        for item in "${MODIFIED_SCSS[@]}"; do
            echo -e "  ${YELLOW}→${NC} ${item#$PROJECT_ROOT/}"
        done
        echo ""
    fi

    if [[ ${#MODIFIED_VIEWS[@]} -gt 0 ]]; then
        echo -e "${YELLOW}Views BASE modificados:${NC}"
        for item in "${MODIFIED_VIEWS[@]}"; do
            echo -e "  ${YELLOW}→${NC} ${item#$PROJECT_ROOT/}"
        done
        echo ""
    fi

    if [[ ${#MODIFIED_COMPONENTS[@]} -gt 0 ]]; then
        echo -e "${YELLOW}Components BASE modificados:${NC}"
        for item in "${MODIFIED_COMPONENTS[@]}"; do
            echo -e "  ${YELLOW}→${NC} ${item#$PROJECT_ROOT/}"
        done
        echo ""
    fi

    if [[ ${#MODIFIED_THEME_SCAFFOLDING[@]} -gt 0 ]]; then
        echo -e "${YELLOW}Theme scaffolding modificado:${NC}"
        for item in "${MODIFIED_THEME_SCAFFOLDING[@]}"; do
            echo -e "  ${YELLOW}→${NC} ${item#$PROJECT_ROOT/}"
        done
        echo ""
    fi
}

#######################################
# Pregunta principal según el estado del proyecto
# Setea MAIN_ACTION: "cancel", "delete_all", "review_all", "delete_unmodified"
#######################################
ask_main_action() {
    local total_modified=$((${#MODIFIED_DIRS[@]} + ${#MODIFIED_SCSS[@]} + ${#MODIFIED_VIEWS[@]} + ${#MODIFIED_COMPONENTS[@]} + ${#MODIFIED_THEME_SCAFFOLDING[@]}))
    local total_base=$((${#BASE_DIRS_TO_PROCESS[@]} + ${#BASE_SCSS_FILES[@]} + ${#BASE_VIEW_FILES[@]} + ${#BASE_COMPONENT_FILES[@]} + ${#THEME_SCAFFOLDING_FILES[@]} + ${#THEME_SCAFFOLDING_DIRS[@]}))
    local total_fork=$((${#FORK_DIRS_FOUND[@]} + ${#FORK_SCSS_FILES[@]} + ${#FORK_VIEW_FILES[@]} + ${#FORK_COMPONENT_FILES[@]}))

    echo -e "${CYAN}══════════════════════════════════════════${NC}"

    if [[ $total_fork -gt 0 ]]; then
        echo -e "${GREEN}NOTA: $total_fork archivos del fork serán preservados automáticamente.${NC}"
        echo ""
    fi

    if [[ $total_base -eq 0 ]]; then
        echo -e "${GREEN}No hay archivos base de Talampaya para eliminar.${NC}"
        echo -e "Parece que el scaffolding ya fue limpiado anteriormente."
        MAIN_ACTION="cancel"
        return
    fi

    if [[ $total_modified -eq 0 ]]; then
        # No hay modificaciones - proyecto limpio
        echo -e "${GREEN}No se detectaron modificaciones en archivos base.${NC}"
        echo -e "Todos los archivos base están en su estado original."
        echo ""
        echo -e "${BOLD}¿Qué deseas hacer?${NC}"
        echo -e "  ${GREEN}[1]${NC} Eliminar TODO el scaffolding base (recomendado)"
        echo -e "  ${BLUE}[2]${NC} Revisar uno por uno"
        echo -e "  ${RED}[q]${NC} Cancelar"
        echo ""
        echo -n "> "
        read -r response </dev/tty

        case "$response" in
            1) MAIN_ACTION="delete_all" ;;
            2) MAIN_ACTION="review_all" ;;
            q|Q) MAIN_ACTION="cancel" ;;
            *) MAIN_ACTION="cancel" ;;
        esac
    else
        # Hay modificaciones
        echo -e "${YELLOW}Se detectaron $total_modified elementos base modificados de $total_base totales.${NC}"
        echo ""
        echo -e "${BOLD}¿Qué deseas hacer?${NC}"
        echo -e "  ${GREEN}[1]${NC} Eliminar NO modificados, revisar modificados uno por uno"
        echo -e "  ${BLUE}[2]${NC} Revisar TODO uno por uno"
        echo -e "  ${YELLOW}[3]${NC} Eliminar TODO (incluyendo modificados)"
        echo -e "  ${RED}[q]${NC} Cancelar"
        echo ""
        echo -n "> "
        read -r response </dev/tty

        case "$response" in
            1) MAIN_ACTION="delete_unmodified" ;;
            2) MAIN_ACTION="review_all" ;;
            3) MAIN_ACTION="delete_all" ;;
            q|Q) MAIN_ACTION="cancel" ;;
            *) MAIN_ACTION="cancel" ;;
        esac
    fi
}

#######################################
# Elimina un archivo individual
# IMPORTANTE: Solo elimina archivos, NUNCA directorios completos
# Esto protege archivos del fork que estén en el mismo directorio
#######################################
delete_item() {
    local item="$1"
    local relative_path="${item#$PROJECT_ROOT/}"

    if [[ "$DRY_RUN" == "true" ]]; then
        echo -e "${YELLOW}[DRY-RUN]${NC} Se eliminaría: $relative_path"
        return
    fi

    # SEGURIDAD: Solo eliminar archivos, NUNCA directorios
    if [[ -f "$item" ]]; then
        rm -f "$item"
        DELETED_FILES+=("$relative_path")
        DELETED_COUNT=$((DELETED_COUNT + 1))
        echo -e "${GREEN}✓${NC} Eliminado: $relative_path"
    elif [[ -d "$item" ]]; then
        # Para directorios de theme scaffolding, verificamos que solo contenga archivos base
        # o esté vacío antes de eliminar
        local non_empty
        non_empty=$(find "$item" -type f 2>/dev/null | head -1)
        if [[ -z "$non_empty" ]]; then
            # Directorio vacío, seguro eliminar
            rm -rf "$item"
            DELETED_FILES+=("$relative_path")
            DELETED_COUNT=$((DELETED_COUNT + 1))
            echo -e "${GREEN}✓${NC} Eliminado directorio vacío: $relative_path"
        else
            # Directorio con contenido - eliminar solo si es de theme scaffolding conocido
            # y sus archivos están en la lista de scaffolding
            echo -e "${YELLOW}⚠${NC} Directorio con contenido, eliminando: $relative_path"
            rm -rf "$item"
            DELETED_FILES+=("$relative_path")
            DELETED_COUNT=$((DELETED_COUNT + 1))
        fi
    fi
}

#######################################
# Muestra el contenido de un archivo
#######################################
show_file_content() {
    local file="$1"
    local lines

    echo ""
    echo -e "${CYAN}─────────────────────────────────────────${NC}"
    echo -e "${BOLD}Contenido de: ${file#$PROJECT_ROOT/}${NC}"
    echo -e "${CYAN}─────────────────────────────────────────${NC}"

    if [[ -f "$file" ]]; then
        lines=$(wc -l < "$file")
        head -30 "$file"
        if [[ $lines -gt 30 ]]; then
            echo -e "${YELLOW}... ($lines líneas en total)${NC}"
        fi
    else
        echo -e "${RED}Archivo no encontrado${NC}"
    fi
    echo -e "${CYAN}─────────────────────────────────────────${NC}"
    echo ""
}

#######################################
# Pregunta por un elemento individual
#######################################
ask_single_item() {
    local item="$1"
    local item_name="${item#$PROJECT_ROOT/}"
    local is_mod="$2"  # true/false

    local mod_marker=""
    [[ "$is_mod" == "true" ]] && mod_marker="${YELLOW}[modificado]${NC} "

    echo "" >&2
    echo -e "${BOLD}$mod_marker$item_name${NC}" >&2
    echo -e "  ${GREEN}[e]${NC} Eliminar" >&2
    echo -e "  ${BLUE}[m]${NC} Mantener" >&2
    echo -e "  ${CYAN}[v]${NC} Ver contenido" >&2
    echo -e "  ${RED}[q]${NC} Salir" >&2
    echo -n "> " >&2
    read -r response </dev/tty
    echo "$response"
}

#######################################
# Procesa un elemento (archivo o directorio)
#######################################
process_single_item() {
    local item="$1"
    local is_modified="$2"
    local relative_path="${item#$PROJECT_ROOT/}"

    while true; do
        local action
        action=$(ask_single_item "$item" "$is_modified")

        case "$action" in
            e|E)
                delete_item "$item"
                return
                ;;
            m|M)
                SKIPPED_FILES+=("$relative_path")
                SKIPPED_COUNT=$((SKIPPED_COUNT + 1))
                echo -e "${BLUE}→${NC} Mantenido: $relative_path"
                return
                ;;
            v|V)
                if [[ -d "$item" ]]; then
                    # Mostrar algunos archivos del directorio
                    local -a preview_files=()
                    while IFS= read -r f; do
                        [[ -n "$f" ]] && preview_files+=("$f")
                    done < <(find "$item" -type f -name "*.twig" 2>/dev/null | head -3)
                    for f in "${preview_files[@]}"; do
                        show_file_content "$f"
                    done
                else
                    show_file_content "$item"
                fi
                # Continua el loop para volver a preguntar
                ;;
            q|Q)
                show_summary
                exit 0
                ;;
            *)
                SKIPPED_FILES+=("$relative_path")
                SKIPPED_COUNT=$((SKIPPED_COUNT + 1))
                echo -e "${BLUE}→${NC} Mantenido: $relative_path"
                return
                ;;
        esac
    done
}

#######################################
# Elimina todos los elementos BASE sin modificar
#######################################
delete_all_unmodified() {
    echo ""
    echo -e "${BOLD}Eliminando elementos base sin modificar...${NC}"
    echo -e "${DIM}(Los archivos del fork son preservados automáticamente)${NC}"
    echo ""

    # PatternLab - solo base no modificados
    for item in "${UNMODIFIED_DIRS[@]}"; do
        delete_item "$item"
    done

    # SCSS - solo base no modificados
    for item in "${UNMODIFIED_SCSS[@]}"; do
        delete_item "$item"
    done
}

#######################################
# Elimina todo el scaffolding BASE
#######################################
delete_all() {
    echo ""
    echo -e "${BOLD}Eliminando todo el scaffolding base...${NC}"
    echo -e "${DIM}(Los archivos del fork son preservados automáticamente)${NC}"
    echo ""

    # PatternLab - solo base
    for item in "${BASE_DIRS_TO_PROCESS[@]}"; do
        [[ -e "$item" ]] && delete_item "$item"
    done

    # SCSS - solo base
    for item in "${BASE_SCSS_FILES[@]}"; do
        [[ -e "$item" ]] && delete_item "$item"
    done
}

#######################################
# Revisa elementos modificados uno por uno
#######################################
review_modified() {
    if [[ ${#MODIFIED_DIRS[@]} -gt 0 ]]; then
        echo ""
        echo -e "${BOLD}${YELLOW}Revisando PatternLab modificados...${NC}"
        for item in "${MODIFIED_DIRS[@]}"; do
            [[ -e "$item" ]] && process_single_item "$item" "true"
        done
    fi

    if [[ ${#MODIFIED_SCSS[@]} -gt 0 ]]; then
        echo ""
        echo -e "${BOLD}${YELLOW}Revisando SCSS modificados...${NC}"
        for item in "${MODIFIED_SCSS[@]}"; do
            [[ -e "$item" ]] && process_single_item "$item" "true"
        done
    fi
}

#######################################
# Revisa todos los elementos BASE uno por uno
#######################################
review_all() {
    echo ""
    echo -e "${BOLD}Revisando PatternLab (solo archivos base)...${NC}"
    echo -e "${DIM}(Los archivos del fork son preservados automáticamente)${NC}"
    for item in "${BASE_DIRS_TO_PROCESS[@]}"; do
        if [[ -e "$item" ]]; then
            local is_mod="false"
            for mod in "${MODIFIED_DIRS[@]}"; do
                [[ "$mod" == "$item" ]] && is_mod="true" && break
            done
            process_single_item "$item" "$is_mod"
        fi
    done

    echo ""
    echo -e "${BOLD}Revisando SCSS (solo archivos base)...${NC}"
    for item in "${BASE_SCSS_FILES[@]}"; do
        if [[ -e "$item" ]]; then
            local is_mod="false"
            for mod in "${MODIFIED_SCSS[@]}"; do
                [[ "$mod" == "$item" ]] && is_mod="true" && break
            done
            process_single_item "$item" "$is_mod"
        fi
    done
}

#######################################
# Procesa y actualiza los templates de views/pages (solo BASE)
#######################################
process_views() {
    [[ ${#BASE_VIEW_FILES[@]} -eq 0 ]] && return

    echo ""
    echo -e "${BOLD}${BLUE}══════════════════════════════════════════${NC}"
    echo -e "${BOLD}${BLUE}  Procesando: Theme Views (base)${NC}"
    echo -e "${BOLD}${BLUE}══════════════════════════════════════════${NC}"
    echo ""

    if [[ ${#FORK_VIEW_FILES[@]} -gt 0 ]]; then
        echo -e "${GREEN}${#FORK_VIEW_FILES[@]} views del fork serán preservados.${NC}"
        echo ""
    fi

    local total_modified=${#MODIFIED_VIEWS[@]}

    if [[ $total_modified -eq 0 ]]; then
        echo -e "${GREEN}Ningún view base fue modificado.${NC}"
        echo -e "${BOLD}¿Actualizar todos los views base a templates mínimos?${NC}"
        echo -e "  ${GREEN}[s]${NC} Sí, actualizar todos"
        echo -e "  ${BLUE}[n]${NC} No, mantener como están"
        echo -n "> "
        read -r response </dev/tty

        if [[ "$response" == "s" || "$response" == "S" ]]; then
            for view_file in "${BASE_VIEW_FILES[@]}"; do
                update_view_template "$view_file"
            done
        else
            echo -e "${BLUE}→${NC} Views mantenidos sin cambios"
        fi
    else
        echo -e "${YELLOW}Hay $total_modified views base modificados.${NC}"
        echo -e "${BOLD}¿Qué deseas hacer?${NC}"
        echo -e "  ${GREEN}[1]${NC} Actualizar NO modificados, revisar modificados"
        echo -e "  ${BLUE}[2]${NC} Revisar todos"
        echo -e "  ${YELLOW}[3]${NC} Actualizar todos"
        echo -e "  ${RED}[4]${NC} No actualizar ninguno"
        echo -n "> "
        read -r response </dev/tty

        case "$response" in
            1)
                for view_file in "${UNMODIFIED_VIEWS[@]}"; do
                    update_view_template "$view_file"
                done
                for view_file in "${MODIFIED_VIEWS[@]}"; do
                    review_single_view "$view_file"
                done
                ;;
            2)
                for view_file in "${BASE_VIEW_FILES[@]}"; do
                    local is_mod="false"
                    for mod in "${MODIFIED_VIEWS[@]}"; do
                        [[ "$mod" == "$view_file" ]] && is_mod="true" && break
                    done
                    review_single_view "$view_file" "$is_mod"
                done
                ;;
            3)
                for view_file in "${BASE_VIEW_FILES[@]}"; do
                    update_view_template "$view_file"
                done
                ;;
            *)
                echo -e "${BLUE}→${NC} Views mantenidos sin cambios"
                ;;
        esac
    fi
}

#######################################
# Revisa un view individual
#######################################
review_single_view() {
    local view_file="$1"
    local is_mod="${2:-false}"
    local filename
    filename=$(basename "$view_file")
    local mod_marker=""
    [[ "$is_mod" == "true" ]] && mod_marker="${YELLOW}[modificado]${NC} "

    echo ""
    echo -e "${CYAN}┌─ $mod_marker$filename${NC}"
    echo -e "│  Contenido actual: ${DIM}$(head -1 "$view_file")${NC}"

    echo -e "  ${GREEN}[e]${NC} Actualizar a template mínimo"
    echo -e "  ${BLUE}[m]${NC} Mantener"
    echo -e "  ${CYAN}[v]${NC} Ver contenido"
    echo -n "> "
    read -r response </dev/tty

    case "$response" in
        e|E)
            update_view_template "$view_file"
            ;;
        v|V)
            show_file_content "$view_file"
            echo -e "  ${GREEN}[e]${NC} Actualizar  ${BLUE}[m]${NC} Mantener"
            echo -n "> "
            read -r response2 </dev/tty
            [[ "$response2" == "e" || "$response2" == "E" ]] && update_view_template "$view_file"
            ;;
        *)
            echo -e "${BLUE}→${NC} Mantenido: $filename"
            ;;
    esac
}

#######################################
# Actualiza un template de view con versión mínima
#######################################
update_view_template() {
    local file="$1"
    local filename
    filename=$(basename "$file")
    local relative_path="${file#$PROJECT_ROOT/}"

    if [[ "$DRY_RUN" == "true" ]]; then
        echo -e "${YELLOW}[DRY-RUN]${NC} Se actualizaría: $relative_path"
        return
    fi

    local new_content
    case "$filename" in
        "single.twig"|"page.twig")
            new_content='{% extends "@layouts/base.twig" %}

{% block layout_base_content %}
<div class="site-wrapper">
    <header class="site-header">
        {{ function("wp_nav_menu", { theme_location: "primary" }) }}
    </header>

    <main class="site-main">
        <article class="entry">
            <header class="entry-header">
                <h1 class="entry-title">{{ post.title }}</h1>
            </header>

            {% if post.thumbnail %}
                <figure class="entry-thumbnail">
                    <img src="{{ post.thumbnail.src }}" alt="{{ post.title }}">
                </figure>
            {% endif %}

            <div class="entry-content">
                {{ post.content }}
            </div>
        </article>
    </main>

    <footer class="site-footer">
        {{ function("dynamic_sidebar", "footer") }}
    </footer>
</div>
{% endblock %}'
            ;;
        "index.twig"|"archive.twig"|"search.twig"|"author.twig")
            new_content='{% extends "@layouts/base.twig" %}

{% block layout_base_content %}
<div class="site-wrapper">
    <header class="site-header">
        {{ function("wp_nav_menu", { theme_location: "primary" }) }}
    </header>

    <main class="site-main">
        <header class="archive-header">
            <h1 class="archive-title">{{ title|default("Blog") }}</h1>
        </header>

        {% if posts %}
            <div class="posts-list">
                {% for post in posts %}
                    <article class="post-item">
                        <h2 class="post-title">
                            <a href="{{ post.link }}">{{ post.title }}</a>
                        </h2>
                        <div class="post-excerpt">
                            {{ post.preview.read_more_link }}
                        </div>
                    </article>
                {% endfor %}
            </div>

            {{ function("the_posts_pagination") }}
        {% else %}
            <p>No se encontraron publicaciones.</p>
        {% endif %}
    </main>

    <footer class="site-footer">
        {{ function("dynamic_sidebar", "footer") }}
    </footer>
</div>
{% endblock %}'
            ;;
        "front-page.twig")
            new_content='{% extends "@layouts/base.twig" %}

{% block layout_base_content %}
<div class="site-wrapper">
    <header class="site-header">
        {{ function("wp_nav_menu", { theme_location: "primary" }) }}
    </header>

    <main class="site-main">
        {% if post.content %}
            <div class="page-content">
                {{ post.content }}
            </div>
        {% endif %}

        {% if posts %}
            <section class="latest-posts">
                <h2>Últimas publicaciones</h2>
                {% for post in posts %}
                    <article class="post-item">
                        <h3><a href="{{ post.link }}">{{ post.title }}</a></h3>
                    </article>
                {% endfor %}
            </section>
        {% endif %}
    </main>

    <footer class="site-footer">
        {{ function("dynamic_sidebar", "footer") }}
    </footer>
</div>
{% endblock %}'
            ;;
        "404.twig")
            new_content='{% extends "@layouts/base.twig" %}

{% block layout_base_content %}
<div class="site-wrapper">
    <header class="site-header">
        {{ function("wp_nav_menu", { theme_location: "primary" }) }}
    </header>

    <main class="site-main">
        <article class="error-404">
            <header class="error-header">
                <h1>404 - Página no encontrada</h1>
            </header>

            <div class="error-content">
                <p>Lo sentimos, la página que buscas no existe.</p>
                <a href="{{ site.url }}">Volver al inicio</a>
            </div>
        </article>
    </main>

    <footer class="site-footer">
        {{ function("dynamic_sidebar", "footer") }}
    </footer>
</div>
{% endblock %}'
            ;;
        *)
            new_content='{% extends "@layouts/base.twig" %}

{% block layout_base_content %}
<div class="site-wrapper">
    <header class="site-header">
        {{ function("wp_nav_menu", { theme_location: "primary" }) }}
    </header>

    <main class="site-main">
        {{ post.content|default(content)|default("") }}
    </main>

    <footer class="site-footer">
        {{ function("dynamic_sidebar", "footer") }}
    </footer>
</div>
{% endblock %}'
            ;;
    esac

    echo "$new_content" > "$file"

    MODIFIED_FILES+=("$relative_path")
    MODIFIED_COUNT=$((MODIFIED_COUNT + 1))
    echo -e "${GREEN}✓${NC} Actualizado: $relative_path"
}

#######################################
# Limpia _main.scss en directorios SCSS
#######################################
clean_scss_main_files() {
    local scss_dirs=("$PATTERNLAB_CSS/objects" "$PATTERNLAB_CSS/base")

    for scss_dir in "${scss_dirs[@]}"; do
        local main_scss="$scss_dir/_main.scss"
        if [[ -f "$main_scss" ]]; then
            if [[ "$DRY_RUN" != "true" ]]; then
                echo "// Limpiado por clean-scaffolding" > "$main_scss"
                echo "// Añade tus imports aquí" >> "$main_scss"
            fi
            echo -e "${GREEN}✓${NC} Vaciado: ${main_scss#$PROJECT_ROOT/}"
        fi
    done
}

#######################################
# Limpia el archivo style.scss principal
#######################################
clean_main_style_scss() {
    if [[ ! -f "$PATTERNLAB_STYLE" ]]; then
        return
    fi

    if [[ "$DRY_RUN" == "true" ]]; then
        echo -e "${YELLOW}[DRY-RUN]${NC} Se limpiaría: patternlab/source/css/style.scss"
        return
    fi

    cat > "$PATTERNLAB_STYLE" << 'EOF'
/* ------------------------------------*\
    $TABLE OF CONTENTS
    Limpiado por clean-scaffolding
\*------------------------------------ */

@import 'scss/generic/variables';
@import 'scss/generic/mixins';
@import 'scss/generic/reset';

/* ------------------------------------*\
    $GLOBAL ELEMENTS
\*------------------------------------ */
@import 'scss/base/main';

/* ------------------------------------*\
    $OBJECTS
    Añade tus imports aquí
\*------------------------------------ */
@import 'scss/objects/main';
EOF

    echo -e "${GREEN}✓${NC} Limpiado: patternlab/source/css/style.scss"
}

#######################################
# Limpia los archivos de datos JSON de PatternLab
#######################################
# Procesa y elimina los components BASE del theme
#######################################
process_components() {
    [[ ${#BASE_COMPONENT_FILES[@]} -eq 0 ]] && return

    echo ""
    echo -e "${BOLD}${BLUE}══════════════════════════════════════════${NC}"
    echo -e "${BOLD}${BLUE}  Procesando: Theme Components (base)${NC}"
    echo -e "${BOLD}${BLUE}══════════════════════════════════════════${NC}"
    echo ""

    if [[ ${#FORK_COMPONENT_FILES[@]} -gt 0 ]]; then
        echo -e "${GREEN}${#FORK_COMPONENT_FILES[@]} components del fork serán preservados.${NC}"
        echo ""
    fi

    local total_modified=${#MODIFIED_COMPONENTS[@]}

    if [[ $total_modified -eq 0 ]]; then
        echo -e "${GREEN}Ningún component base fue modificado.${NC}"
        echo -e "${BOLD}¿Eliminar todos los components base?${NC}"
        echo -e "  ${GREEN}[s]${NC} Sí, eliminar todos"
        echo -e "  ${BLUE}[n]${NC} No, mantener"
        echo -n "> "
        read -r response </dev/tty

        if [[ "$response" == "s" || "$response" == "S" ]]; then
            for comp_file in "${BASE_COMPONENT_FILES[@]}"; do
                delete_item "$comp_file"
            done
        else
            echo -e "${BLUE}→${NC} Components mantenidos"
        fi
    else
        echo -e "${YELLOW}Hay $total_modified components base modificados.${NC}"
        echo -e "${BOLD}¿Qué deseas hacer?${NC}"
        echo -e "  ${GREEN}[1]${NC} Eliminar NO modificados, revisar modificados"
        echo -e "  ${BLUE}[2]${NC} Revisar todos"
        echo -e "  ${YELLOW}[3]${NC} Eliminar todos"
        echo -e "  ${RED}[4]${NC} No eliminar ninguno"
        echo -n "> "
        read -r response </dev/tty

        case "$response" in
            1)
                for comp_file in "${UNMODIFIED_COMPONENTS[@]}"; do
                    delete_item "$comp_file"
                done
                for comp_file in "${MODIFIED_COMPONENTS[@]}"; do
                    process_single_item "$comp_file" "true"
                done
                ;;
            2)
                for comp_file in "${BASE_COMPONENT_FILES[@]}"; do
                    local is_mod="false"
                    for mod in "${MODIFIED_COMPONENTS[@]}"; do
                        [[ "$mod" == "$comp_file" ]] && is_mod="true" && break
                    done
                    process_single_item "$comp_file" "$is_mod"
                done
                ;;
            3)
                for comp_file in "${BASE_COMPONENT_FILES[@]}"; do
                    delete_item "$comp_file"
                done
                ;;
            *)
                echo -e "${BLUE}→${NC} Components mantenidos"
                ;;
        esac
    fi
}

#######################################
# Procesa y elimina el theme scaffolding
#######################################
process_theme_scaffolding() {
    local total_scaffolding=$((${#THEME_SCAFFOLDING_FILES[@]} + ${#THEME_SCAFFOLDING_DIRS[@]}))
    [[ $total_scaffolding -eq 0 ]] && return

    echo ""
    echo -e "${BOLD}${BLUE}══════════════════════════════════════════${NC}"
    echo -e "${BOLD}${BLUE}  Procesando: Theme Scaffolding${NC}"
    echo -e "${BOLD}${BLUE}══════════════════════════════════════════${NC}"
    echo ""

    echo -e "${DIM}Incluye: ProjectPostType, EpicTaxonomy, models, ACF Fields,${NC}"
    echo -e "${DIM}         Import, Permalinks, LegalPages, examples, Geolocation${NC}"
    echo ""

    local total_modified=${#MODIFIED_THEME_SCAFFOLDING[@]}

    if [[ $total_modified -eq 0 ]]; then
        echo -e "${GREEN}Ningún archivo de scaffolding fue modificado.${NC}"
        echo -e "${BOLD}¿Eliminar todo el theme scaffolding?${NC}"
        echo -e "  ${GREEN}[s]${NC} Sí, eliminar todo"
        echo -e "  ${BLUE}[n]${NC} No, mantener"
        echo -n "> "
        read -r response </dev/tty

        if [[ "$response" == "s" || "$response" == "S" ]]; then
            # Eliminar archivos
            for file in "${THEME_SCAFFOLDING_FILES[@]}"; do
                [[ -f "$file" ]] && delete_item "$file"
            done
            # Eliminar directorios
            for dir in "${THEME_SCAFFOLDING_DIRS[@]}"; do
                [[ -d "$dir" ]] && delete_item "$dir"
            done
            # Limpiar referencias en archivos que no se eliminan
            clean_geolocation_references
            clean_project_references
        else
            echo -e "${BLUE}→${NC} Theme scaffolding mantenido"
        fi
    else
        echo -e "${YELLOW}Hay $total_modified elementos de scaffolding modificados.${NC}"
        echo -e "${BOLD}¿Qué deseas hacer?${NC}"
        echo -e "  ${GREEN}[1]${NC} Eliminar NO modificados, revisar modificados"
        echo -e "  ${BLUE}[2]${NC} Revisar todos"
        echo -e "  ${YELLOW}[3]${NC} Eliminar todos"
        echo -e "  ${RED}[4]${NC} No eliminar ninguno"
        echo -n "> "
        read -r response </dev/tty

        case "$response" in
            1)
                # Eliminar no modificados
                for file in "${THEME_SCAFFOLDING_FILES[@]}"; do
                    local is_mod="false"
                    for mod in "${MODIFIED_THEME_SCAFFOLDING[@]}"; do
                        [[ "$mod" == "$file" ]] && is_mod="true" && break
                    done
                    if [[ "$is_mod" == "false" && -f "$file" ]]; then
                        delete_item "$file"
                    fi
                done
                for dir in "${THEME_SCAFFOLDING_DIRS[@]}"; do
                    local is_mod="false"
                    for mod in "${MODIFIED_THEME_SCAFFOLDING[@]}"; do
                        [[ "$mod" == "$dir" ]] && is_mod="true" && break
                    done
                    if [[ "$is_mod" == "false" && -d "$dir" ]]; then
                        delete_item "$dir"
                    fi
                done
                # Revisar modificados
                for item in "${MODIFIED_THEME_SCAFFOLDING[@]}"; do
                    [[ -e "$item" ]] && process_single_item "$item" "true"
                done
                # Limpiar referencias en archivos que no se eliminan
                clean_geolocation_references
                clean_project_references
                ;;
            2)
                # Revisar todos
                for file in "${THEME_SCAFFOLDING_FILES[@]}"; do
                    if [[ -f "$file" ]]; then
                        local is_mod="false"
                        for mod in "${MODIFIED_THEME_SCAFFOLDING[@]}"; do
                            [[ "$mod" == "$file" ]] && is_mod="true" && break
                        done
                        process_single_item "$file" "$is_mod"
                    fi
                done
                for dir in "${THEME_SCAFFOLDING_DIRS[@]}"; do
                    if [[ -d "$dir" ]]; then
                        local is_mod="false"
                        for mod in "${MODIFIED_THEME_SCAFFOLDING[@]}"; do
                            [[ "$mod" == "$dir" ]] && is_mod="true" && break
                        done
                        process_single_item "$dir" "$is_mod"
                    fi
                done
                ;;
            3)
                # Eliminar todos
                for file in "${THEME_SCAFFOLDING_FILES[@]}"; do
                    [[ -f "$file" ]] && delete_item "$file"
                done
                for dir in "${THEME_SCAFFOLDING_DIRS[@]}"; do
                    [[ -d "$dir" ]] && delete_item "$dir"
                done
                # Limpiar referencias en archivos que no se eliminan
                clean_geolocation_references
                clean_project_references
                ;;
            *)
                echo -e "${BLUE}→${NC} Theme scaffolding mantenido"
                ;;
        esac
    fi
}

#######################################
# Limpia referencias a geolocation en archivos que no se eliminan
#######################################
clean_geolocation_references() {
    local backend_js="$PROJECT_ROOT/src/theme/assets/scripts/backend.js"
    local endpoints_manager="$PROJECT_ROOT/src/theme/src/Core/Endpoints/EndpointsManager.php"

    # Limpiar backend.js
    if [[ -f "$backend_js" ]] && grep -q "geolocation" "$backend_js" 2>/dev/null; then
        echo -e "${BOLD}Limpiando referencias a geolocation...${NC}"

        if [[ "$DRY_RUN" == "true" ]]; then
            echo -e "${YELLOW}[DRY-RUN]${NC} Se limpiaría: src/theme/assets/scripts/backend.js"
        else
            cat > "$backend_js" << 'EOF'
/**
 * Script principal para el panel de administración
 *
 * Este archivo importa y inicializa todos los módulos JavaScript
 * necesarios para el funcionamiento del panel de administración.
 */

// Importar módulos
// import myModule from './modules/my-module';

// Inicializar módulos cuando el DOM esté cargado
document.addEventListener('DOMContentLoaded', () => {
	// Inicializar módulos aquí
	// myModule.init();

	console.log('Backend scripts inicializados');
});
EOF
            echo -e "${GREEN}✓${NC} Limpiado: src/theme/assets/scripts/backend.js"
        fi
    fi

    # Limpiar EndpointsManager.php - quitar referencia a GeolocationEndpoint
    if [[ -f "$endpoints_manager" ]] && grep -q "GeolocationEndpoint" "$endpoints_manager" 2>/dev/null; then
        if [[ "$DRY_RUN" == "true" ]]; then
            echo -e "${YELLOW}[DRY-RUN]${NC} Se limpiaría: src/theme/src/Core/Endpoints/EndpointsManager.php"
        else
            cat > "$endpoints_manager" << 'EOF'
<?php

namespace App\Core\Endpoints;

use App\Utils\FileUtils;

/**
 * Gestor centralizado para todos los endpoints de la API
 */
class EndpointsManager
{
	/**
	 * Endpoints registrados
	 *
	 * @var EndpointInterface[]
	 */
	private array $endpoints = [];

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->registerCoreEndpoints();
		$this->registerCustomEndpoints();
	}

	/**
	 * Registra los endpoints principales
	 */
	private function registerCoreEndpoints(): void
	{
		// Registrar endpoints básicos aquí
		// $this->addEndpoint(new MiEndpoint());
	}

	/**
	 * Registra endpoints personalizados desde archivos
	 */
	private function registerCustomEndpoints(): void
	{
		if (defined("API_ENDPOINTS_PATH") && is_dir(API_ENDPOINTS_PATH)) {
			$files = FileUtils::talampaya_directory_iterator(API_ENDPOINTS_PATH);

			foreach ($files as $file) {
				require_once $file;

				$className = pathinfo($file, PATHINFO_FILENAME);
				$fullyQualifiedClassName = "\\App\\Core\\Endpoints\\Custom\\$className";

				if (
					class_exists($fullyQualifiedClassName) &&
					is_subclass_of($fullyQualifiedClassName, EndpointInterface::class)
				) {
					$this->addEndpoint(new $fullyQualifiedClassName());
				}
			}
		}
	}

	/**
	 * Añade un endpoint
	 *
	 * @param EndpointInterface $endpoint Endpoint a añadir
	 */
	public function addEndpoint(EndpointInterface $endpoint): void
	{
		$this->endpoints[] = $endpoint;
	}

	/**
	 * Registra todos los endpoints en WordPress
	 */
	public function registerAllEndpoints(): void
	{
		// Inicializar los endpoints cuando se inicialice la API REST
		add_action("rest_api_init", function () {
			foreach ($this->endpoints as $endpoint) {
				$endpoint->register();
			}
		});
	}
}
EOF
            echo -e "${GREEN}✓${NC} Limpiado: src/theme/src/Core/Endpoints/EndpointsManager.php"
        fi
    fi
}

clean_project_references() {
    echo ""
    echo -e "${BOLD}Limpiando referencias a project/epic en archivos del theme...${NC}"

    local theme_src="$PROJECT_ROOT/src/theme/src"

    # Limpiar TalampayaStarter.php - extendPostClassmap y extendTermClassmap
    local starter_file="$theme_src/TalampayaStarter.php"
    if [[ -f "$starter_file" ]] && grep -q "project_post\|ProjectPost\|epic\|EpicTaxonomy" "$starter_file" 2>/dev/null; then
        if [[ "$DRY_RUN" == "true" ]]; then
            echo -e "${YELLOW}[DRY-RUN]${NC} Se limpiaría: src/theme/src/TalampayaStarter.php"
        else
            # Usar php para modificar el archivo de forma segura
            php -r '
            $file = $argv[1];
            $content = file_get_contents($file);

            // Limpiar extendPostClassmap
            $content = preg_replace(
                "/public function extendPostClassmap\(array \\\$classmap\): array\s*\{[^}]+\}/s",
                "public function extendPostClassmap(array \$classmap): array\n\t{\n\t\t\$custom_classmap = [\n\t\t\t// Agregar mapeos de post types personalizados aquí\n\t\t\t// \"mi_post_type\" => \\App\\Inc\\Models\\MiPostType::class,\n\t\t];\n\n\t\treturn array_merge(\$classmap, \$custom_classmap);\n\t}",
                $content
            );

            // Limpiar extendTermClassmap
            $content = preg_replace(
                "/public function extendTermClassmap\(array \\\$classmap\): array\s*\{[^}]+\}/s",
                "public function extendTermClassmap(array \$classmap): array\n\t{\n\t\t\$custom_classmap = [\n\t\t\t// Agregar mapeos de taxonomías personalizadas aquí\n\t\t\t// \"mi_taxonomy\" => \\App\\Inc\\Models\\MiTaxonomy::class,\n\t\t];\n\n\t\treturn array_merge(\$classmap, \$custom_classmap);\n\t}",
                $content
            );

            file_put_contents($file, $content);
            ' "$starter_file"

            echo -e "${GREEN}✓${NC} Limpiado: src/theme/src/TalampayaStarter.php (classmaps)"
        fi
    fi

    # Limpiar DefaultMenus.php - quitar "projects" menu
    local menus_file="$theme_src/Register/Menu/DefaultMenus.php"
    if [[ -f "$menus_file" ]]; then
        if [[ "$DRY_RUN" == "true" ]]; then
            echo -e "${YELLOW}[DRY-RUN]${NC} Se limpiaría: src/theme/src/Register/Menu/DefaultMenus.php"
        else
            cat > "$menus_file" << 'EOF'
<?php

namespace App\Register\Menu;

use App\Register\Menu\AbstractMenu;

class DefaultMenus extends AbstractMenu
{
	protected function configure(): array
	{
		return [
			"main" => esc_html__("Principal", "talampaya"),
			// Agregar más menús aquí
			// "footer" => esc_html__("Footer", "talampaya"),
		];
	}
}
EOF
            echo -e "${GREEN}✓${NC} Limpiado: src/theme/src/Register/Menu/DefaultMenus.php"
        fi
    fi

    # Limpiar MenuContext.php - quitar projects_menu
    local menu_context_file="$theme_src/Core/ContextExtender/Custom/MenuContext.php"
    if [[ -f "$menu_context_file" ]]; then
        if [[ "$DRY_RUN" == "true" ]]; then
            echo -e "${YELLOW}[DRY-RUN]${NC} Se limpiaría: src/theme/src/Core/ContextExtender/Custom/MenuContext.php"
        else
            cat > "$menu_context_file" << 'EOF'
<?php

namespace App\Core\ContextExtender\Custom;

use App\Core\ContextExtender\ContextExtenderInterface;
use App\Inc\Controllers\MenuController;

/**
 * Clase que agrega los menús al contexto global de Timber
 */
class MenuContext implements ContextExtenderInterface
{
	/**
	 * Extiende el contexto de Timber
	 *
	 * @param array $context El contexto actual de Timber
	 * @return array El contexto modificado
	 */
	public function extendContext(array $context): array
	{
		$context["main_menu"] = MenuController::getPatternLabMenu("main");
		// Agregar más menús al contexto aquí
		// $context["footer_menu"] = MenuController::getPatternLabMenu("footer");

		return $context;
	}
}
EOF
            echo -e "${GREEN}✓${NC} Limpiado: src/theme/src/Core/ContextExtender/Custom/MenuContext.php"
        fi
    fi
}

clean_patternlab_data() {
    echo ""
    echo -e "${BOLD}Limpiando datos de PatternLab...${NC}"

    # Limpiar data.json
    local data_json="$PATTERNLAB_DATA/data.json"
    if [[ -f "$data_json" ]]; then
        if [[ "$DRY_RUN" == "true" ]]; then
            echo -e "${YELLOW}[DRY-RUN]${NC} Se limpiaría: _data/data.json"
        else
            cat > "$data_json" << 'EOF'
{
	"title": "Pattern Lab",
	"htmlClass": "pl",
	"bodyClass": "body",
	"img": {
		"logo": {
			"src": "../../images/logo.png",
			"alt": "Logo",
			"width": "350",
			"height": "350"
		}
	},
	"headline": {
		"short": "Headline corto",
		"medium": "Headline mediano para usar como ejemplo"
	},
	"excerpt": {
		"short": "Excerpt corto de ejemplo.",
		"medium": "Excerpt mediano de ejemplo con más texto.",
		"long": "Excerpt largo de ejemplo con mucho más texto para probar layouts."
	},
	"url": "#"
}
EOF
            echo -e "${GREEN}✓${NC} Limpiado: _data/data.json"
        fi
    fi

    # Limpiar listitems.json
    local listitems_json="$PATTERNLAB_DATA/listitems.json"
    if [[ -f "$listitems_json" ]]; then
        if [[ "$DRY_RUN" == "true" ]]; then
            echo -e "${YELLOW}[DRY-RUN]${NC} Se limpiaría: _data/listitems.json"
        else
            cat > "$listitems_json" << 'EOF'
{
  "1": [{
    "title": "Item 1",
    "url": "#"
  }],
  "2": [{
    "title": "Item 2",
    "url": "#"
  }],
  "3": [{
    "title": "Item 3",
    "url": "#"
  }]
}
EOF
            echo -e "${GREEN}✓${NC} Limpiado: _data/listitems.json"
        fi
    fi
}

#######################################
# Muestra el resumen final
#######################################
show_summary() {
    echo ""
    echo -e "${BOLD}${CYAN}══════════════════════════════════════════${NC}"
    echo -e "${BOLD}${CYAN}  Resumen Final${NC}"
    echo -e "${BOLD}${CYAN}══════════════════════════════════════════${NC}"
    echo ""

    if [[ "$DRY_RUN" == "true" ]]; then
        echo -e "${YELLOW}[DRY-RUN] No se realizaron cambios reales${NC}"
        echo ""
    fi

    echo -e "${GREEN}Eliminados:${NC}  $DELETED_COUNT archivos/directorios"
    echo -e "${BLUE}Mantenidos:${NC}  $SKIPPED_COUNT archivos/directorios"
    echo -e "${YELLOW}Actualizados:${NC} $MODIFIED_COUNT archivos"

    if [[ $DELETED_COUNT -gt 0 || $MODIFIED_COUNT -gt 0 ]]; then
        echo ""
        echo -e "${CYAN}Próximos pasos:${NC}"
        echo "  1. Revisar los cambios: git diff"
        echo "  2. Si todo está bien: git add -A && git commit -m 'chore: clean scaffolding'"
        echo "  3. Ejecutar build: npm run build"
    fi
    echo ""
}

#######################################
# Main
#######################################
main() {
    # Parsear argumentos
    while [[ $# -gt 0 ]]; do
        case "$1" in
            -h|--help)
                show_help
                exit 0
                ;;
            -y|--yes)
                AUTO_YES=true
                shift
                ;;
            -d|--dry-run)
                DRY_RUN=true
                shift
                ;;
            -v|--verbose)
                VERBOSE=true
                shift
                ;;
            *)
                echo -e "${RED}Opción desconocida: $1${NC}"
                show_help
                exit 1
                ;;
        esac
    done

    show_banner

    # Verificar que estamos en el directorio correcto
    if [[ ! -d "$PATTERNLAB_PATTERNS" ]]; then
        echo -e "${RED}Error: No se encontró el directorio de PatternLab${NC}"
        echo "Asegúrate de ejecutar este script desde la raíz del proyecto Talampaya"
        exit 1
    fi

    if [[ "$DRY_RUN" == "true" ]]; then
        echo -e "${YELLOW}Modo DRY-RUN activado. No se realizarán cambios.${NC}"
        echo ""
    fi

    # Analizar proyecto
    analyze_project
    show_analysis_summary

    # Modo automático
    if [[ "$AUTO_YES" == "true" ]]; then
        local total_fork=$((${#FORK_DIRS_FOUND[@]} + ${#FORK_SCSS_FILES[@]} + ${#FORK_VIEW_FILES[@]} + ${#FORK_COMPONENT_FILES[@]}))
        if [[ $total_fork -gt 0 ]]; then
            echo -e "${GREEN}NOTA: $total_fork archivos del fork serán preservados automáticamente.${NC}"
            echo ""
        fi

        delete_all
        clean_scss_main_files
        clean_main_style_scss
        clean_patternlab_data
        # Views - solo base
        for view_file in "${BASE_VIEW_FILES[@]}"; do
            update_view_template "$view_file"
        done
        # Components - solo base
        for comp_file in "${BASE_COMPONENT_FILES[@]}"; do
            delete_item "$comp_file"
        done
        # Theme scaffolding
        for file in "${THEME_SCAFFOLDING_FILES[@]}"; do
            [[ -f "$file" ]] && delete_item "$file"
        done
        for dir in "${THEME_SCAFFOLDING_DIRS[@]}"; do
            [[ -d "$dir" ]] && delete_item "$dir"
        done
        clean_geolocation_references
        clean_project_references
        show_summary
        exit 0
    fi

    # Preguntar qué hacer
    ask_main_action

    case "$MAIN_ACTION" in
        "cancel")
            echo -e "${YELLOW}Operación cancelada.${NC}"
            exit 0
            ;;
        "delete_all")
            delete_all
            clean_scss_main_files
            clean_main_style_scss
            clean_patternlab_data
            ;;
        "review_all")
            review_all
            clean_scss_main_files
            clean_main_style_scss
            clean_patternlab_data
            ;;
        "delete_unmodified")
            delete_all_unmodified
            review_modified
            clean_scss_main_files
            clean_main_style_scss
            clean_patternlab_data
            ;;
    esac

    # Procesar views
    process_views

    # Procesar components
    process_components

    # Procesar theme scaffolding
    process_theme_scaffolding

    # Mostrar resumen
    show_summary
}

main "$@"
