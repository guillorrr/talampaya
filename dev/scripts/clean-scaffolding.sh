#!/bin/bash
#
# clean-scaffolding.sh
# Limpia el contenido de ejemplo de PatternLab para preparar el proyecto base para forks
#
# Uso: npm run clean:scaffolding
#      o directamente: bash dev/scripts/clean-scaffolding.sh
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

# Arrays para análisis inicial
declare -a ALL_DIRS_TO_PROCESS=()
declare -a MODIFIED_DIRS=()
declare -a UNMODIFIED_DIRS=()
declare -a MISSING_DIRS=()
declare -a ALL_SCSS_FILES=()
declare -a MODIFIED_SCSS=()
declare -a UNMODIFIED_SCSS=()
declare -a ALL_VIEW_FILES=()
declare -a MODIFIED_VIEWS=()
declare -a UNMODIFIED_VIEWS=()
declare -a ALL_COMPONENT_FILES=()
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
# Muestra el banner inicial
#######################################
show_banner() {
    echo ""
    echo -e "${CYAN}╔══════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${CYAN}║${NC}  ${BOLD}Talampaya - Clean Scaffolding${NC}                               ${CYAN}║${NC}"
    echo -e "${CYAN}║${NC}  Limpia contenido de ejemplo para preparar forks            ${CYAN}║${NC}"
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
    echo "  -y, --yes       Modo no interactivo (elimina todo sin preguntar)"
    echo "  -d, --dry-run   Muestra qué se eliminaría sin hacer cambios"
    echo "  -v, --verbose   Muestra más detalles durante la ejecución"
    echo ""
    echo "Este script elimina:"
    echo "  - Atoms, molecules, organisms de PatternLab"
    echo "  - Templates y pages de ejemplo de PatternLab"
    echo "  - Estilos SCSS asociados a componentes"
    echo "  - Actualiza views/pages para usar templates mínimos locales"
    echo ""
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
#######################################
analyze_project() {
    echo -e "${BOLD}Analizando proyecto...${NC}"
    echo ""

    # Analizar directorios de PatternLab
    local pattern_types=("atoms" "molecules" "organisms" "templates" "pages" "macros")

    for type in "${pattern_types[@]}"; do
        local type_dir="$PATTERNLAB_PATTERNS/$type"

        if [[ ! -d "$type_dir" ]]; then
            MISSING_DIRS+=("$type")
            continue
        fi

        # Obtener subdirectorios
        while IFS= read -r -d '' subdir; do
            local subdir_name="${subdir#$PATTERNLAB_PATTERNS/}"
            ALL_DIRS_TO_PROCESS+=("$subdir")

            if is_modified "$subdir"; then
                MODIFIED_DIRS+=("$subdir")
            else
                UNMODIFIED_DIRS+=("$subdir")
            fi
        done < <(find "$type_dir" -mindepth 1 -maxdepth 1 -type d -print0 2>/dev/null | sort -z)

        # Archivos sueltos en el directorio raíz del tipo
        while IFS= read -r file; do
            [[ -z "$file" ]] && continue
            ALL_DIRS_TO_PROCESS+=("$file")
            if is_modified "$file"; then
                MODIFIED_DIRS+=("$file")
            else
                UNMODIFIED_DIRS+=("$file")
            fi
        done < <(find "$type_dir" -maxdepth 1 -type f \( -name "*.twig" -o -name "*.json" -o -name "*.md" \) 2>/dev/null | sort)
    done

    # Analizar archivos SCSS
    local scss_dirs=("$PATTERNLAB_CSS/objects" "$PATTERNLAB_CSS/base")
    for scss_dir in "${scss_dirs[@]}"; do
        [[ ! -d "$scss_dir" ]] && continue

        while IFS= read -r file; do
            [[ -z "$file" ]] && continue
            [[ "$(basename "$file")" == "_main.scss" ]] && continue  # Ignorar _main.scss
            ALL_SCSS_FILES+=("$file")
            if is_modified "$file"; then
                MODIFIED_SCSS+=("$file")
            else
                UNMODIFIED_SCSS+=("$file")
            fi
        done < <(find "$scss_dir" -type f -name "*.scss" 2>/dev/null | sort)
    done

    # Analizar views/pages
    local views_pages="$THEME_VIEWS/pages"
    if [[ -d "$views_pages" ]]; then
        for twig_file in "$views_pages"/*.twig; do
            [[ ! -f "$twig_file" ]] && continue

            # Solo archivos que incluyen PatternLab
            if grep -qE '@templates|@organisms|@molecules|@atoms' "$twig_file" 2>/dev/null; then
                ALL_VIEW_FILES+=("$twig_file")
                if is_modified "$twig_file"; then
                    MODIFIED_VIEWS+=("$twig_file")
                else
                    UNMODIFIED_VIEWS+=("$twig_file")
                fi
            fi
        done
    fi

    # Analizar views/components
    local views_components="$THEME_VIEWS/components"
    if [[ -d "$views_components" ]]; then
        for twig_file in "$views_components"/*.twig; do
            [[ ! -f "$twig_file" ]] && continue
            ALL_COMPONENT_FILES+=("$twig_file")
            if is_modified "$twig_file"; then
                MODIFIED_COMPONENTS+=("$twig_file")
            else
                UNMODIFIED_COMPONENTS+=("$twig_file")
            fi
        done
    fi

    # Analizar theme scaffolding (archivos de ejemplo del theme)
    local theme_src="$PROJECT_ROOT/src/theme/src"
    local theme_assets="$PROJECT_ROOT/src/theme/assets"
    local theme_blocks="$PROJECT_ROOT/src/theme/blocks"

    # Archivos individuales de scaffolding
    local scaffolding_files=(
        "$theme_src/Register/PostType/ProjectPostType.php"
        "$theme_src/Register/Taxonomy/EpicTaxonomy.php"
        "$theme_src/Inc/Models/ProjectPost.php"
        "$theme_src/Inc/Models/EpicTaxonomy.php"
        "$theme_src/Features/ContentGenerator/Generators/ProjectPostGenerator.php"
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

    # Directorios de scaffolding
    local scaffolding_dirs=(
        "$theme_blocks/example"
        "$theme_src/Integrations/Geolocation"
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

    # PatternLab
    echo -e "${BOLD}PatternLab Patterns:${NC}"
    echo -e "  Total:       ${#ALL_DIRS_TO_PROCESS[@]} elementos"
    echo -e "  ${GREEN}Sin cambios: ${#UNMODIFIED_DIRS[@]}${NC}"
    echo -e "  ${YELLOW}Modificados: ${#MODIFIED_DIRS[@]}${NC}"
    if [[ ${#MISSING_DIRS[@]} -gt 0 ]]; then
        echo -e "  ${DIM}No existen:  ${#MISSING_DIRS[@]} (${MISSING_DIRS[*]})${NC}"
    fi
    echo ""

    # SCSS
    echo -e "${BOLD}Estilos SCSS:${NC}"
    echo -e "  Total:       ${#ALL_SCSS_FILES[@]} archivos"
    echo -e "  ${GREEN}Sin cambios: ${#UNMODIFIED_SCSS[@]}${NC}"
    echo -e "  ${YELLOW}Modificados: ${#MODIFIED_SCSS[@]}${NC}"
    echo ""

    # Views
    echo -e "${BOLD}Theme Views (con refs a PatternLab):${NC}"
    echo -e "  Total:       ${#ALL_VIEW_FILES[@]} archivos"
    echo -e "  ${GREEN}Sin cambios: ${#UNMODIFIED_VIEWS[@]}${NC}"
    echo -e "  ${YELLOW}Modificados: ${#MODIFIED_VIEWS[@]}${NC}"
    echo ""

    # Components
    echo -e "${BOLD}Theme Components:${NC}"
    echo -e "  Total:       ${#ALL_COMPONENT_FILES[@]} archivos"
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
        echo -e "  ${DIM}(ProjectPostType, EpicTaxonomy, example block, Geolocation)${NC}"
        echo ""
    fi

    # Mostrar lista de modificados si hay
    if [[ ${#MODIFIED_DIRS[@]} -gt 0 ]]; then
        echo -e "${YELLOW}Elementos modificados en PatternLab:${NC}"
        for item in "${MODIFIED_DIRS[@]}"; do
            echo -e "  ${YELLOW}→${NC} ${item#$PROJECT_ROOT/}"
        done
        echo ""
    fi

    if [[ ${#MODIFIED_SCSS[@]} -gt 0 ]]; then
        echo -e "${YELLOW}Archivos SCSS modificados:${NC}"
        for item in "${MODIFIED_SCSS[@]}"; do
            echo -e "  ${YELLOW}→${NC} ${item#$PROJECT_ROOT/}"
        done
        echo ""
    fi

    if [[ ${#MODIFIED_VIEWS[@]} -gt 0 ]]; then
        echo -e "${YELLOW}Views modificados:${NC}"
        for item in "${MODIFIED_VIEWS[@]}"; do
            echo -e "  ${YELLOW}→${NC} ${item#$PROJECT_ROOT/}"
        done
        echo ""
    fi

    if [[ ${#MODIFIED_COMPONENTS[@]} -gt 0 ]]; then
        echo -e "${YELLOW}Components modificados:${NC}"
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
    local total_items=$((${#ALL_DIRS_TO_PROCESS[@]} + ${#ALL_SCSS_FILES[@]} + ${#ALL_VIEW_FILES[@]} + ${#ALL_COMPONENT_FILES[@]} + ${#THEME_SCAFFOLDING_FILES[@]} + ${#THEME_SCAFFOLDING_DIRS[@]}))

    echo -e "${CYAN}══════════════════════════════════════════${NC}"

    if [[ $total_modified -eq 0 ]]; then
        # No hay modificaciones - proyecto limpio
        echo -e "${GREEN}No se detectaron modificaciones.${NC}"
        echo -e "Todos los archivos están en su estado original."
        echo ""
        echo -e "${BOLD}¿Qué deseas hacer?${NC}"
        echo -e "  ${GREEN}[1]${NC} Eliminar TODO el scaffolding (recomendado)"
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
        echo -e "${YELLOW}Se detectaron $total_modified elementos modificados de $total_items totales.${NC}"
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
# Elimina un archivo o directorio
#######################################
delete_item() {
    local item="$1"
    local relative_path="${item#$PROJECT_ROOT/}"

    if [[ "$DRY_RUN" == "true" ]]; then
        echo -e "${YELLOW}[DRY-RUN]${NC} Se eliminaría: $relative_path"
        return
    fi

    if [[ -d "$item" ]]; then
        rm -rf "$item"
    elif [[ -f "$item" ]]; then
        rm -f "$item"
    fi

    DELETED_FILES+=("$relative_path")
    DELETED_COUNT=$((DELETED_COUNT + 1))
    echo -e "${GREEN}✓${NC} Eliminado: $relative_path"
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
# Elimina todos los elementos sin modificar
#######################################
delete_all_unmodified() {
    echo ""
    echo -e "${BOLD}Eliminando elementos sin modificar...${NC}"
    echo ""

    # PatternLab
    for item in "${UNMODIFIED_DIRS[@]}"; do
        delete_item "$item"
    done

    # SCSS
    for item in "${UNMODIFIED_SCSS[@]}"; do
        delete_item "$item"
    done
}

#######################################
# Elimina absolutamente todo
#######################################
delete_all() {
    echo ""
    echo -e "${BOLD}Eliminando todo el scaffolding...${NC}"
    echo ""

    # PatternLab - todos
    for item in "${ALL_DIRS_TO_PROCESS[@]}"; do
        [[ -e "$item" ]] && delete_item "$item"
    done

    # SCSS - todos
    for item in "${ALL_SCSS_FILES[@]}"; do
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
# Revisa todos los elementos uno por uno
#######################################
review_all() {
    echo ""
    echo -e "${BOLD}Revisando PatternLab...${NC}"
    for item in "${ALL_DIRS_TO_PROCESS[@]}"; do
        if [[ -e "$item" ]]; then
            local is_mod="false"
            for mod in "${MODIFIED_DIRS[@]}"; do
                [[ "$mod" == "$item" ]] && is_mod="true" && break
            done
            process_single_item "$item" "$is_mod"
        fi
    done

    echo ""
    echo -e "${BOLD}Revisando SCSS...${NC}"
    for item in "${ALL_SCSS_FILES[@]}"; do
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
# Procesa y actualiza los templates de views/pages
#######################################
process_views() {
    [[ ${#ALL_VIEW_FILES[@]} -eq 0 ]] && return

    echo ""
    echo -e "${BOLD}${BLUE}══════════════════════════════════════════${NC}"
    echo -e "${BOLD}${BLUE}  Procesando: Theme Views${NC}"
    echo -e "${BOLD}${BLUE}══════════════════════════════════════════${NC}"
    echo ""

    local total_modified=${#MODIFIED_VIEWS[@]}

    if [[ $total_modified -eq 0 ]]; then
        echo -e "${GREEN}Ningún view fue modificado.${NC}"
        echo -e "${BOLD}¿Actualizar todos a templates mínimos?${NC}"
        echo -e "  ${GREEN}[s]${NC} Sí, actualizar todos"
        echo -e "  ${BLUE}[n]${NC} No, mantener como están"
        echo -n "> "
        read -r response </dev/tty

        if [[ "$response" == "s" || "$response" == "S" ]]; then
            for view_file in "${ALL_VIEW_FILES[@]}"; do
                update_view_template "$view_file"
            done
        else
            echo -e "${BLUE}→${NC} Views mantenidos sin cambios"
        fi
    else
        echo -e "${YELLOW}Hay $total_modified views modificados.${NC}"
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
                for view_file in "${ALL_VIEW_FILES[@]}"; do
                    local is_mod="false"
                    for mod in "${MODIFIED_VIEWS[@]}"; do
                        [[ "$mod" == "$view_file" ]] && is_mod="true" && break
                    done
                    review_single_view "$view_file" "$is_mod"
                done
                ;;
            3)
                for view_file in "${ALL_VIEW_FILES[@]}"; do
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
#######################################
# Procesa y elimina los components del theme
#######################################
process_components() {
    [[ ${#ALL_COMPONENT_FILES[@]} -eq 0 ]] && return

    echo ""
    echo -e "${BOLD}${BLUE}══════════════════════════════════════════${NC}"
    echo -e "${BOLD}${BLUE}  Procesando: Theme Components${NC}"
    echo -e "${BOLD}${BLUE}══════════════════════════════════════════${NC}"
    echo ""

    local total_modified=${#MODIFIED_COMPONENTS[@]}

    if [[ $total_modified -eq 0 ]]; then
        echo -e "${GREEN}Ningún component fue modificado.${NC}"
        echo -e "${BOLD}¿Eliminar todos los components?${NC}"
        echo -e "  ${GREEN}[s]${NC} Sí, eliminar todos"
        echo -e "  ${BLUE}[n]${NC} No, mantener"
        echo -n "> "
        read -r response </dev/tty

        if [[ "$response" == "s" || "$response" == "S" ]]; then
            for comp_file in "${ALL_COMPONENT_FILES[@]}"; do
                delete_item "$comp_file"
            done
        else
            echo -e "${BLUE}→${NC} Components mantenidos"
        fi
    else
        echo -e "${YELLOW}Hay $total_modified components modificados.${NC}"
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
                for comp_file in "${ALL_COMPONENT_FILES[@]}"; do
                    local is_mod="false"
                    for mod in "${MODIFIED_COMPONENTS[@]}"; do
                        [[ "$mod" == "$comp_file" ]] && is_mod="true" && break
                    done
                    process_single_item "$comp_file" "$is_mod"
                done
                ;;
            3)
                for comp_file in "${ALL_COMPONENT_FILES[@]}"; do
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

    echo -e "${DIM}Incluye: ProjectPostType, EpicTaxonomy, ProjectPost model,${NC}"
    echo -e "${DIM}         EpicTaxonomy model, example block, Geolocation${NC}"
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
            # Limpiar referencias a geolocation
            clean_geolocation_references
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
                # Limpiar referencias a geolocation
                clean_geolocation_references
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
                # Limpiar referencias a geolocation
                clean_geolocation_references
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
        delete_all
        clean_scss_main_files
        clean_main_style_scss
        clean_patternlab_data
        for view_file in "${ALL_VIEW_FILES[@]}"; do
            update_view_template "$view_file"
        done
        for comp_file in "${ALL_COMPONENT_FILES[@]}"; do
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
