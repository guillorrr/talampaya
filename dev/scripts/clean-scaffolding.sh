#!/bin/bash
#
# clean-scaffolding.sh
# Limpia el contenido de ejemplo de PatternLab para preparar el proyecto base para forks
#
# Uso: npm run clean:scaffolding
#      o directamente: bash dev/scripts/clean-scaffolding.sh
#
# IMPORTANTE: Este script compara contra el repositorio upstream de Talampaya
# para determinar qué archivos son del scaffolding base. Solo elimina archivos
# que existen en upstream. Archivos nuevos del fork son preservados automáticamente.
#

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

# URL del repositorio upstream de Talampaya
# Puedes sobrescribir esta URL con la variable de entorno TALAMPAYA_UPSTREAM_URL
UPSTREAM_URL="${TALAMPAYA_UPSTREAM_URL:-git@github.com:guillorrr/talampaya.git}"
UPSTREAM_BRANCH="${TALAMPAYA_UPSTREAM_BRANCH:-master}"

# Directorios de PatternLab y theme
PATTERNLAB_PATTERNS="patternlab/source/_patterns"
PATTERNLAB_CSS="patternlab/source/css/scss"
PATTERNLAB_DATA="patternlab/source/_data"
PATTERNLAB_STYLE="patternlab/source/css/style.scss"
THEME_VIEWS="src/theme/views"
THEME_SRC="src/theme/src"
THEME_ASSETS="src/theme/assets"
THEME_BLOCKS="src/theme/blocks"

# Patrones de archivos/directorios del scaffolding base a limpiar
# Estos patrones se usan para filtrar qué archivos de upstream son scaffolding
SCAFFOLDING_PATTERNS=(
    # PatternLab patterns
    "patternlab/source/_patterns/atoms/"
    "patternlab/source/_patterns/molecules/"
    "patternlab/source/_patterns/organisms/"
    "patternlab/source/_patterns/templates/"
    "patternlab/source/_patterns/pages/"
    "patternlab/source/_patterns/macros/"
    # SCSS
    "patternlab/source/css/scss/base/"
    "patternlab/source/css/scss/objects/"
    # Theme views
    "src/theme/views/pages/"
    "src/theme/views/components/"
    # Theme scaffolding específico
    "src/theme/src/Register/PostType/ProjectPostType.php"
    "src/theme/src/Register/Taxonomy/EpicTaxonomy.php"
    "src/theme/src/Inc/Models/ProjectPost.php"
    "src/theme/src/Inc/Models/EpicTaxonomy.php"
    "src/theme/src/Features/ContentGenerator/Generators/ProjectPostGenerator.php"
    "src/theme/src/Features/ContentGenerator/Generators/LegalPagesGenerator.php"
    "src/theme/src/Features/Acf/Fields/ProjectPost/"
    "src/theme/src/Features/Import/ProjectImport.php"
    "src/theme/src/Inc/Services/ProjectImportService.php"
    "src/theme/src/Features/Admin/Pages/ImportPagesSettings.php"
    "src/theme/src/Mockups/projects.csv"
    "src/theme/src/Features/Permalinks/CustomPermalinks.php"
    "src/theme/src/Core/Endpoints/Custom/example-endpoint.php"
    "src/theme/src/Features/Acf/Blocks/Modifiers/example-modifier.php"
    "src/theme/src/Features/Admin/Pages/GeolocationSettings.php"
    "src/theme/src/Features/Acf/Blocks/Modifiers/geolocation-modifier.php"
    "src/theme/src/Core/Endpoints/GeolocationEndpoint.php"
    "src/theme/assets/scripts/modules/geolocation.js"
    "src/theme/blocks/example/"
    "src/theme/src/Integrations/Geolocation/"
    "src/theme/src/Features/DefaultContent/"
)

# Contadores globales
DELETED_COUNT=0
SKIPPED_COUNT=0
MODIFIED_COUNT=0

# Arrays para tracking
declare -a UPSTREAM_FILES=()
declare -a BASE_FILES=()
declare -a FORK_FILES=()
declare -a MODIFIED_FILES=()
declare -a UNMODIFIED_FILES=()

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
    echo -e "${CYAN}║${NC}  Compara con upstream, preserva archivos del fork           ${CYAN}║${NC}"
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
    echo "FUNCIONAMIENTO:"
    echo "  Este script compara tu proyecto con el repositorio upstream de Talampaya"
    echo "  para determinar qué archivos son del scaffolding base original."
    echo ""
    echo "  - Archivos que EXISTEN en upstream: Son del scaffolding base (candidatos a eliminar)"
    echo "  - Archivos que NO EXISTEN en upstream: Son del fork (preservados automáticamente)"
    echo "  - Archivos base MODIFICADOS localmente: Se pregunta antes de eliminar"
    echo ""
    echo "REQUISITOS:"
    echo "  - Git instalado"
    echo "  - Conexión a internet (para fetch de upstream)"
    echo ""
    echo "El script configura automáticamente el remote 'upstream' si no existe."
    echo ""
}

#######################################
# Verifica y configura el remote upstream
# Returns:
#   0 si upstream está configurado correctamente
#   1 si hay error
#######################################
setup_upstream() {
    echo -e "${BOLD}Verificando configuración de upstream...${NC}"

    # Verificar si el remote upstream existe
    if ! git -C "$PROJECT_ROOT" remote get-url upstream &>/dev/null; then
        echo -e "${YELLOW}Remote 'upstream' no configurado.${NC}"
        echo -e "Configurando upstream: ${CYAN}$UPSTREAM_URL${NC}"

        if ! git -C "$PROJECT_ROOT" remote add upstream "$UPSTREAM_URL" 2>/dev/null; then
            echo -e "${RED}Error: No se pudo agregar el remote upstream${NC}"
            return 1
        fi
        echo -e "${GREEN}✓${NC} Remote upstream agregado"
    else
        local current_url
        current_url=$(git -C "$PROJECT_ROOT" remote get-url upstream)
        echo -e "${GREEN}✓${NC} Remote upstream configurado: ${DIM}$current_url${NC}"
    fi

    # Fetch del upstream para tener la información actualizada
    echo -e "${BOLD}Obteniendo información de upstream...${NC}"
    if ! git -C "$PROJECT_ROOT" fetch upstream "$UPSTREAM_BRANCH" --quiet 2>/dev/null; then
        echo -e "${RED}Error: No se pudo hacer fetch de upstream${NC}"
        echo -e "${DIM}Verifica tu conexión a internet y que el repositorio exista${NC}"
        return 1
    fi
    echo -e "${GREEN}✓${NC} Información de upstream actualizada"

    return 0
}

#######################################
# Obtiene la lista de archivos del scaffolding desde upstream
#######################################
get_upstream_scaffolding_files() {
    echo -e "${BOLD}Obteniendo lista de archivos de scaffolding desde upstream...${NC}"

    # Obtener todos los archivos de upstream
    local all_upstream_files
    all_upstream_files=$(git -C "$PROJECT_ROOT" ls-tree -r --name-only "upstream/$UPSTREAM_BRANCH" 2>/dev/null)

    if [[ -z "$all_upstream_files" ]]; then
        echo -e "${RED}Error: No se pudieron obtener archivos de upstream${NC}"
        return 1
    fi

    # Filtrar solo los archivos que coinciden con los patrones de scaffolding
    while IFS= read -r file; do
        [[ -z "$file" ]] && continue

        for pattern in "${SCAFFOLDING_PATTERNS[@]}"; do
            if [[ "$file" == "$pattern"* ]]; then
                UPSTREAM_FILES+=("$file")
                break
            fi
        done
    done <<< "$all_upstream_files"

    echo -e "${GREEN}✓${NC} Encontrados ${#UPSTREAM_FILES[@]} archivos de scaffolding en upstream"
    return 0
}

#######################################
# Verifica si un archivo fue modificado respecto a upstream
# Arguments:
#   $1 - Ruta del archivo (relativa al proyecto)
# Returns:
#   0 si fue modificado, 1 si no
#######################################
is_modified_from_upstream() {
    local file="$1"

    # Si el archivo no existe localmente, no está modificado (será saltado)
    if [[ ! -f "$PROJECT_ROOT/$file" ]]; then
        return 1
    fi

    # Comparar el archivo local con la versión de upstream
    if git -C "$PROJECT_ROOT" diff --quiet "upstream/$UPSTREAM_BRANCH" -- "$file" 2>/dev/null; then
        return 1  # No modificado (igual a upstream)
    else
        return 0  # Modificado (diferente de upstream)
    fi
}

#######################################
# Analiza el proyecto comparando con upstream
#######################################
analyze_project() {
    echo -e "${BOLD}Analizando proyecto...${NC}"
    echo ""

    local local_files_in_scaffolding_dirs=()

    # Obtener archivos locales en los directorios de scaffolding
    for pattern in "${SCAFFOLDING_PATTERNS[@]}"; do
        local full_path="$PROJECT_ROOT/$pattern"

        if [[ -d "$full_path" ]]; then
            # Es un directorio, obtener todos los archivos dentro
            while IFS= read -r file; do
                [[ -z "$file" ]] && continue
                local relative_file="${file#$PROJECT_ROOT/}"
                local_files_in_scaffolding_dirs+=("$relative_file")
            done < <(find "$full_path" -type f 2>/dev/null)
        elif [[ -f "$full_path" ]]; then
            # Es un archivo específico
            local_files_in_scaffolding_dirs+=("$pattern")
        fi
    done

    # Clasificar archivos
    for file in "${local_files_in_scaffolding_dirs[@]}"; do
        # Verificar si el archivo existe en upstream
        local in_upstream=false
        for upstream_file in "${UPSTREAM_FILES[@]}"; do
            if [[ "$file" == "$upstream_file" ]]; then
                in_upstream=true
                break
            fi
        done

        if [[ "$in_upstream" == "true" ]]; then
            # Archivo existe en upstream = es del scaffolding base
            BASE_FILES+=("$file")

            if is_modified_from_upstream "$file"; then
                MODIFIED_FILES+=("$file")
            else
                UNMODIFIED_FILES+=("$file")
            fi
        else
            # Archivo NO existe en upstream = es del fork
            FORK_FILES+=("$file")
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

    # Archivos del fork (protegidos)
    if [[ ${#FORK_FILES[@]} -gt 0 ]]; then
        echo -e "${GREEN}${BOLD}Archivos del FORK detectados (protegidos):${NC}"
        echo -e "  ${GREEN}Total: ${#FORK_FILES[@]} archivos NO serán eliminados${NC}"
        echo ""
    fi

    # Scaffolding base
    echo -e "${BOLD}${BLUE}Scaffolding BASE de Talampaya (de upstream):${NC}"
    echo -e "  Total:       ${#BASE_FILES[@]} archivos"
    echo -e "  ${GREEN}Sin cambios: ${#UNMODIFIED_FILES[@]}${NC} (idénticos a upstream)"
    echo -e "  ${YELLOW}Modificados: ${#MODIFIED_FILES[@]}${NC} (diferentes de upstream)"
    echo ""

    # Mostrar archivos del fork si verbose
    if [[ "$VERBOSE" == "true" && ${#FORK_FILES[@]} -gt 0 ]]; then
        echo -e "${GREEN}Archivos del fork (protegidos):${NC}"
        for file in "${FORK_FILES[@]}"; do
            echo -e "  ${GREEN}✓${NC} $file"
        done
        echo ""
    fi

    # Mostrar archivos modificados
    if [[ ${#MODIFIED_FILES[@]} -gt 0 ]]; then
        echo -e "${YELLOW}Archivos base MODIFICADOS localmente:${NC}"
        for file in "${MODIFIED_FILES[@]}"; do
            echo -e "  ${YELLOW}→${NC} $file"
        done
        echo ""
    fi
}

#######################################
# Pregunta principal según el estado del proyecto
#######################################
ask_main_action() {
    local total_base=${#BASE_FILES[@]}
    local total_modified=${#MODIFIED_FILES[@]}
    local total_fork=${#FORK_FILES[@]}

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
        echo -e "${GREEN}Ningún archivo base fue modificado.${NC}"
        echo -e "Todos los archivos base son idénticos a upstream."
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
        echo -e "${YELLOW}Se detectaron $total_modified archivos base modificados de $total_base totales.${NC}"
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
# Elimina un archivo
#######################################
delete_file() {
    local file="$1"
    local full_path="$PROJECT_ROOT/$file"

    if [[ "$DRY_RUN" == "true" ]]; then
        echo -e "${YELLOW}[DRY-RUN]${NC} Se eliminaría: $file"
        DELETED_COUNT=$((DELETED_COUNT + 1))
        return
    fi

    if [[ -f "$full_path" ]]; then
        rm -f "$full_path"
        DELETED_COUNT=$((DELETED_COUNT + 1))
        echo -e "${GREEN}✓${NC} Eliminado: $file"
    fi
}

#######################################
# Muestra el contenido de un archivo
#######################################
show_file_content() {
    local file="$1"
    local full_path="$PROJECT_ROOT/$file"

    echo ""
    echo -e "${CYAN}─────────────────────────────────────────${NC}"
    echo -e "${BOLD}Contenido de: $file${NC}"
    echo -e "${CYAN}─────────────────────────────────────────${NC}"

    if [[ -f "$full_path" ]]; then
        local lines
        lines=$(wc -l < "$full_path")
        head -30 "$full_path"
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
# Muestra diff de un archivo contra upstream
#######################################
show_file_diff() {
    local file="$1"

    echo ""
    echo -e "${CYAN}─────────────────────────────────────────${NC}"
    echo -e "${BOLD}Diferencias con upstream: $file${NC}"
    echo -e "${CYAN}─────────────────────────────────────────${NC}"

    git -C "$PROJECT_ROOT" diff "upstream/$UPSTREAM_BRANCH" -- "$file" 2>/dev/null | head -50

    echo -e "${CYAN}─────────────────────────────────────────${NC}"
    echo ""
}

#######################################
# Pregunta por un archivo individual
#######################################
process_single_file() {
    local file="$1"
    local is_modified="$2"

    local mod_marker=""
    [[ "$is_modified" == "true" ]] && mod_marker="${YELLOW}[modificado]${NC} "

    while true; do
        echo "" >&2
        echo -e "${BOLD}$mod_marker$file${NC}" >&2
        echo -e "  ${GREEN}[e]${NC} Eliminar" >&2
        echo -e "  ${BLUE}[m]${NC} Mantener" >&2
        echo -e "  ${CYAN}[v]${NC} Ver contenido" >&2
        [[ "$is_modified" == "true" ]] && echo -e "  ${YELLOW}[d]${NC} Ver diff con upstream" >&2
        echo -e "  ${RED}[q]${NC} Salir" >&2
        echo -n "> " >&2
        read -r response </dev/tty

        case "$response" in
            e|E)
                delete_file "$file"
                return
                ;;
            m|M)
                SKIPPED_COUNT=$((SKIPPED_COUNT + 1))
                echo -e "${BLUE}→${NC} Mantenido: $file"
                return
                ;;
            v|V)
                show_file_content "$file"
                ;;
            d|D)
                [[ "$is_modified" == "true" ]] && show_file_diff "$file"
                ;;
            q|Q)
                show_summary
                exit 0
                ;;
            *)
                SKIPPED_COUNT=$((SKIPPED_COUNT + 1))
                echo -e "${BLUE}→${NC} Mantenido: $file"
                return
                ;;
        esac
    done
}

#######################################
# Elimina todos los archivos base
#######################################
delete_all_base() {
    echo ""
    echo -e "${BOLD}Eliminando archivos base...${NC}"
    echo -e "${DIM}(Los archivos del fork son preservados automáticamente)${NC}"
    echo ""

    for file in "${BASE_FILES[@]}"; do
        delete_file "$file"
    done
}

#######################################
# Elimina archivos base no modificados
#######################################
delete_unmodified() {
    echo ""
    echo -e "${BOLD}Eliminando archivos base no modificados...${NC}"
    echo ""

    for file in "${UNMODIFIED_FILES[@]}"; do
        delete_file "$file"
    done
}

#######################################
# Revisa archivos modificados uno por uno
#######################################
review_modified() {
    if [[ ${#MODIFIED_FILES[@]} -gt 0 ]]; then
        echo ""
        echo -e "${BOLD}${YELLOW}Revisando archivos modificados...${NC}"
        for file in "${MODIFIED_FILES[@]}"; do
            process_single_file "$file" "true"
        done
    fi
}

#######################################
# Revisa todos los archivos base uno por uno
#######################################
review_all() {
    echo ""
    echo -e "${BOLD}Revisando archivos base...${NC}"

    for file in "${BASE_FILES[@]}"; do
        local is_mod="false"
        for mod in "${MODIFIED_FILES[@]}"; do
            [[ "$mod" == "$file" ]] && is_mod="true" && break
        done
        process_single_file "$file" "$is_mod"
    done
}

#######################################
# Pregunta al usuario qué hacer con un archivo auxiliar modificado
# Arguments:
#   $1 - Ruta del archivo (relativa al proyecto)
#   $2 - Descripción del archivo
# Returns:
#   0 si debe sobrescribir, 1 si debe preservar
#######################################
ask_auxiliary_file_action() {
    local file="$1"
    local description="$2"

    # En modo auto, preservar archivos modificados
    if [[ "$AUTO_YES" == "true" ]]; then
        echo -e "${YELLOW}→${NC} Preservado (modificado): $file"
        return 1
    fi

    while true; do
        echo "" >&2
        echo -e "${YELLOW}[modificado]${NC} ${BOLD}$file${NC}" >&2
        echo -e "  ${DIM}$description${NC}" >&2
        echo -e "  ${GREEN}[s]${NC} Sobrescribir con versión limpia" >&2
        echo -e "  ${BLUE}[p]${NC} Preservar contenido del fork" >&2
        echo -e "  ${CYAN}[d]${NC} Ver diferencias con upstream" >&2
        echo -n "> " >&2
        read -r response </dev/tty

        case "$response" in
            s|S)
                return 0
                ;;
            p|P)
                echo -e "${BLUE}→${NC} Preservado: $file"
                return 1
                ;;
            d|D)
                show_file_diff "$file"
                ;;
            *)
                echo -e "${BLUE}→${NC} Preservado: $file"
                return 1
                ;;
        esac
    done
}

#######################################
# Limpia archivos auxiliares (style.scss, _main.scss, data.json, backend.js)
# IMPORTANTE: Verifica si los archivos fueron modificados respecto a upstream
# antes de sobrescribirlos. Si están modificados, pregunta al usuario.
#######################################
clean_auxiliary_files() {
    echo ""
    echo -e "${BOLD}Limpiando archivos auxiliares...${NC}"

    # Limpiar style.scss principal
    local style_file="$PROJECT_ROOT/$PATTERNLAB_STYLE"
    if [[ -f "$style_file" ]]; then
        local should_clean=true

        # Verificar si fue modificado respecto a upstream
        if is_modified_from_upstream "$PATTERNLAB_STYLE"; then
            if ! ask_auxiliary_file_action "$PATTERNLAB_STYLE" "Contiene imports SCSS personalizados"; then
                should_clean=false
            fi
        fi

        if [[ "$should_clean" == "true" ]]; then
            if [[ "$DRY_RUN" != "true" ]]; then
                cat > "$style_file" << 'EOF'
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
            fi
            echo -e "${GREEN}✓${NC} Limpiado: $PATTERNLAB_STYLE"
        fi
    fi

    # Crear/limpiar _main.scss en objects y base
    for dir in "objects" "base"; do
        local main_file_rel="$PATTERNLAB_CSS/$dir/_main.scss"
        local main_file="$PROJECT_ROOT/$main_file_rel"
        local dir_path="$PROJECT_ROOT/$PATTERNLAB_CSS/$dir"
        local should_clean=true

        # Verificar si fue modificado respecto a upstream
        if [[ -f "$main_file" ]] && is_modified_from_upstream "$main_file_rel"; then
            if ! ask_auxiliary_file_action "$main_file_rel" "Contiene imports SCSS personalizados para $dir"; then
                should_clean=false
            fi
        fi

        if [[ "$should_clean" == "true" ]]; then
            if [[ "$DRY_RUN" != "true" ]]; then
                # Asegurar que el directorio existe
                mkdir -p "$dir_path"
                # Crear archivo _main.scss (vacío con comentario)
                cat > "$main_file" << 'EOF'
// Limpiado por clean-scaffolding
// Añade tus imports aquí
EOF
            fi
            echo -e "${GREEN}✓${NC} Creado: $main_file_rel"
        fi
    done

    # Limpiar data.json
    local data_file_rel="$PATTERNLAB_DATA/data.json"
    local data_file="$PROJECT_ROOT/$data_file_rel"
    if [[ -f "$data_file" ]]; then
        local should_clean=true

        # Verificar si fue modificado respecto a upstream
        if is_modified_from_upstream "$data_file_rel"; then
            if ! ask_auxiliary_file_action "$data_file_rel" "Contiene datos globales personalizados para PatternLab"; then
                should_clean=false
            fi
        fi

        if [[ "$should_clean" == "true" ]]; then
            if [[ "$DRY_RUN" != "true" ]]; then
                cat > "$data_file" << 'EOF'
{
	"title": "Pattern Lab",
	"htmlClass": "pl",
	"bodyClass": "body",
	"img": {
		"logo": {
			"src": "../../images/logo.png",
			"alt": "Logo"
		}
	},
	"headline": {
		"short": "Headline",
		"medium": "Headline mediano de ejemplo"
	},
	"excerpt": {
		"short": "Excerpt corto.",
		"medium": "Excerpt mediano de ejemplo."
	},
	"url": "#"
}
EOF
            fi
            echo -e "${GREEN}✓${NC} Limpiado: $data_file_rel"
        fi
    fi

    # Limpiar listitems.json
    local listitems_file_rel="$PATTERNLAB_DATA/listitems.json"
    local listitems_file="$PROJECT_ROOT/$listitems_file_rel"
    if [[ -f "$listitems_file" ]]; then
        local should_clean=true

        # Verificar si fue modificado respecto a upstream
        if is_modified_from_upstream "$listitems_file_rel"; then
            if ! ask_auxiliary_file_action "$listitems_file_rel" "Contiene datos de listas personalizados para PatternLab"; then
                should_clean=false
            fi
        fi

        if [[ "$should_clean" == "true" ]]; then
            if [[ "$DRY_RUN" != "true" ]]; then
                cat > "$listitems_file" << 'EOF'
{
	"1": [{
		"title": "Item 1",
		"headline": {
			"short": "Título corto",
			"medium": "Título mediano de ejemplo"
		},
		"excerpt": {
			"short": "Descripción corta.",
			"medium": "Descripción mediana de ejemplo."
		},
		"url": "#"
	}],
	"2": [{
		"title": "Item 2",
		"headline": {
			"short": "Título corto",
			"medium": "Título mediano de ejemplo"
		},
		"excerpt": {
			"short": "Descripción corta.",
			"medium": "Descripción mediana de ejemplo."
		},
		"url": "#"
	}],
	"3": [{
		"title": "Item 3",
		"headline": {
			"short": "Título corto",
			"medium": "Título mediano de ejemplo"
		},
		"excerpt": {
			"short": "Descripción corta.",
			"medium": "Descripción mediana de ejemplo."
		},
		"url": "#"
	}]
}
EOF
            fi
            echo -e "${GREEN}✓${NC} Limpiado: $listitems_file_rel"
        fi
    fi

    # Limpiar backend.js (quitar imports de módulos eliminados como geolocation)
    local backend_js_rel="$THEME_ASSETS/scripts/backend.js"
    local backend_js="$PROJECT_ROOT/$backend_js_rel"
    if [[ -f "$backend_js" ]]; then
        local should_clean=true

        # Verificar si fue modificado respecto a upstream
        if is_modified_from_upstream "$backend_js_rel"; then
            if ! ask_auxiliary_file_action "$backend_js_rel" "Contiene imports de módulos JavaScript personalizados"; then
                should_clean=false
            fi
        fi

        if [[ "$should_clean" == "true" ]]; then
            if [[ "$DRY_RUN" != "true" ]]; then
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
            fi
            echo -e "${GREEN}✓${NC} Limpiado: $backend_js_rel"
        fi
    fi

    # Crear templates Twig mínimos para WordPress
    create_minimal_twig_templates
}

#######################################
# Crea templates Twig mínimos para que WordPress funcione
#######################################
create_minimal_twig_templates() {
    echo ""
    echo -e "${BOLD}Creando templates Twig mínimos para WordPress...${NC}"

    local pages_dir="$PROJECT_ROOT/$THEME_VIEWS/pages"

    if [[ "$DRY_RUN" != "true" ]]; then
        mkdir -p "$pages_dir"

        # page.twig
        cat > "$pages_dir/page.twig" << 'EOF'
{# Template base para páginas - Limpiado por clean-scaffolding #}
{% extends "@layouts/base.twig" %}

{% block layout_base_content %}
	<main class="container py-5">
		<article class="page-content">
			<h1>{{ post.title }}</h1>
			<div class="content">
				{{ post.content }}
			</div>
		</article>
	</main>
{% endblock %}
EOF
        echo -e "${GREEN}✓${NC} Creado: $THEME_VIEWS/pages/page.twig"

        # single.twig
        cat > "$pages_dir/single.twig" << 'EOF'
{# Template base para posts - Limpiado por clean-scaffolding #}
{% extends "@layouts/base.twig" %}

{% block layout_base_content %}
	<main class="container py-5">
		<article class="post-content">
			<h1>{{ post.title }}</h1>
			<div class="post-meta mb-3">
				<span class="date">{{ post.date }}</span>
				{% if post.author %}
					<span class="author">por {{ post.author.name }}</span>
				{% endif %}
			</div>
			<div class="content">
				{{ post.content }}
			</div>
		</article>
	</main>
{% endblock %}
EOF
        echo -e "${GREEN}✓${NC} Creado: $THEME_VIEWS/pages/single.twig"

        # index.twig
        cat > "$pages_dir/index.twig" << 'EOF'
{# Template base para listado de posts - Limpiado por clean-scaffolding #}
{% extends "@layouts/base.twig" %}

{% block layout_base_content %}
	<main class="container py-5">
		<h1>{{ title|default('Blog') }}</h1>

		{% if posts %}
			<div class="posts-list">
				{% for post in posts %}
					<article class="post-item mb-4">
						<h2><a href="{{ post.link }}">{{ post.title }}</a></h2>
						<div class="post-meta">
							<span class="date">{{ post.date }}</span>
						</div>
						<div class="excerpt">
							{{ post.preview.read_more_link }}
						</div>
					</article>
				{% endfor %}
			</div>

			{% if pagination %}
				<nav class="pagination">
					{{ pagination }}
				</nav>
			{% endif %}
		{% else %}
			<p>No hay publicaciones.</p>
		{% endif %}
	</main>
{% endblock %}
EOF
        echo -e "${GREEN}✓${NC} Creado: $THEME_VIEWS/pages/index.twig"

        # home.twig
        cat > "$pages_dir/home.twig" << 'EOF'
{# Template para la página de blog (home.php) - Limpiado por clean-scaffolding #}
{% extends "@pages/index.twig" %}
EOF
        echo -e "${GREEN}✓${NC} Creado: $THEME_VIEWS/pages/home.twig"

        # front-page.twig
        cat > "$pages_dir/front-page.twig" << 'EOF'
{# Template para la página principal - Limpiado por clean-scaffolding #}
{% extends "@layouts/base.twig" %}

{% block layout_base_content %}
	<main class="container py-5">
		<article class="front-page-content">
			<h1>{{ post.title }}</h1>
			<div class="content">
				{{ post.content }}
			</div>
		</article>
	</main>
{% endblock %}
EOF
        echo -e "${GREEN}✓${NC} Creado: $THEME_VIEWS/pages/front-page.twig"

        # archive.twig
        cat > "$pages_dir/archive.twig" << 'EOF'
{# Template para archivos - Limpiado por clean-scaffolding #}
{% extends "@layouts/base.twig" %}

{% block layout_base_content %}
	<main class="container py-5">
		<h1>{{ title }}</h1>

		{% if description %}
			<div class="archive-description mb-4">
				{{ description }}
			</div>
		{% endif %}

		{% if posts %}
			<div class="posts-list">
				{% for post in posts %}
					<article class="post-item mb-4">
						<h2><a href="{{ post.link }}">{{ post.title }}</a></h2>
						<div class="post-meta">
							<span class="date">{{ post.date }}</span>
						</div>
						<div class="excerpt">
							{{ post.preview.read_more_link }}
						</div>
					</article>
				{% endfor %}
			</div>

			{% if pagination %}
				<nav class="pagination">
					{{ pagination }}
				</nav>
			{% endif %}
		{% else %}
			<p>No hay publicaciones en este archivo.</p>
		{% endif %}
	</main>
{% endblock %}
EOF
        echo -e "${GREEN}✓${NC} Creado: $THEME_VIEWS/pages/archive.twig"

        # search.twig
        cat > "$pages_dir/search.twig" << 'EOF'
{# Template para resultados de búsqueda - Limpiado por clean-scaffolding #}
{% extends "@layouts/base.twig" %}

{% block layout_base_content %}
	<main class="container py-5">
		<h1>Resultados de búsqueda: "{{ search_query }}"</h1>

		{% if posts %}
			<p>Se encontraron {{ posts|length }} resultados.</p>

			<div class="search-results">
				{% for post in posts %}
					<article class="post-item mb-4">
						<h2><a href="{{ post.link }}">{{ post.title }}</a></h2>
						<div class="excerpt">
							{{ post.preview.read_more_link }}
						</div>
					</article>
				{% endfor %}
			</div>

			{% if pagination %}
				<nav class="pagination">
					{{ pagination }}
				</nav>
			{% endif %}
		{% else %}
			<p>No se encontraron resultados para "{{ search_query }}".</p>
		{% endif %}
	</main>
{% endblock %}
EOF
        echo -e "${GREEN}✓${NC} Creado: $THEME_VIEWS/pages/search.twig"

        # author.twig
        cat > "$pages_dir/author.twig" << 'EOF'
{# Template para páginas de autor - Limpiado por clean-scaffolding #}
{% extends "@layouts/base.twig" %}

{% block layout_base_content %}
	<main class="container py-5">
		<h1>{{ title }}</h1>

		{% if author.description %}
			<div class="author-bio mb-4">
				{{ author.description }}
			</div>
		{% endif %}

		{% if posts %}
			<h2>Publicaciones</h2>
			<div class="posts-list">
				{% for post in posts %}
					<article class="post-item mb-4">
						<h3><a href="{{ post.link }}">{{ post.title }}</a></h3>
						<div class="post-meta">
							<span class="date">{{ post.date }}</span>
						</div>
					</article>
				{% endfor %}
			</div>

			{% if pagination %}
				<nav class="pagination">
					{{ pagination }}
				</nav>
			{% endif %}
		{% else %}
			<p>Este autor no tiene publicaciones.</p>
		{% endif %}
	</main>
{% endblock %}
EOF
        echo -e "${GREEN}✓${NC} Creado: $THEME_VIEWS/pages/author.twig"

        # 404.twig
        cat > "$pages_dir/404.twig" << 'EOF'
{# Template para página 404 - Limpiado por clean-scaffolding #}
{% extends "@layouts/base.twig" %}

{% block layout_base_content %}
	<main class="container py-5 text-center">
		<h1>404</h1>
		<h2>Página no encontrada</h2>
		<p class="lead">Lo sentimos, la página que buscas no existe.</p>
		<a href="{{ site.url }}" class="btn btn-primary">Volver al inicio</a>
	</main>
{% endblock %}
EOF
        echo -e "${GREEN}✓${NC} Creado: $THEME_VIEWS/pages/404.twig"

        # single-password.twig
        cat > "$pages_dir/single-password.twig" << 'EOF'
{# Template para posts protegidos con contraseña - Limpiado por clean-scaffolding #}
{% extends "@layouts/base.twig" %}

{% block layout_base_content %}
	<main class="container py-5">
		<article class="post-content">
			<h1>{{ post.title }}</h1>
			<div class="password-form">
				<p>Este contenido está protegido con contraseña.</p>
				{{ function('get_the_password_form') }}
			</div>
		</article>
	</main>
{% endblock %}
EOF
        echo -e "${GREEN}✓${NC} Creado: $THEME_VIEWS/pages/single-password.twig"

    else
        echo -e "${YELLOW}[DRY-RUN]${NC} Se crearían templates mínimos en $THEME_VIEWS/pages/"
    fi
}

#######################################
# Limpia referencias en archivos del theme
# IMPORTANTE: Verifica si los archivos fueron modificados respecto a upstream
# antes de sobrescribirlos. Si están modificados, pregunta al usuario.
#######################################
clean_theme_references() {
    echo ""
    echo -e "${BOLD}Limpiando referencias en archivos del theme...${NC}"

    local theme_src="$PROJECT_ROOT/$THEME_SRC"

    # Solo limpiar si los archivos de scaffolding fueron eliminados
    # Verificar si ProjectPostType fue eliminado
    if [[ ! -f "$theme_src/Register/PostType/ProjectPostType.php" ]]; then

        # Limpiar TalampayaStarter.php
        local starter_file_rel="$THEME_SRC/TalampayaStarter.php"
        local starter_file="$PROJECT_ROOT/$starter_file_rel"
        if [[ -f "$starter_file" ]] && grep -q "project_post\|ProjectPost\|epic\|EpicTaxonomy" "$starter_file" 2>/dev/null; then
            local should_clean=true

            # Verificar si fue modificado respecto a upstream
            if is_modified_from_upstream "$starter_file_rel"; then
                if ! ask_auxiliary_file_action "$starter_file_rel" "Contiene mapeos de post types y taxonomías personalizados"; then
                    should_clean=false
                fi
            fi

            if [[ "$should_clean" == "true" ]]; then
                if [[ "$DRY_RUN" != "true" ]]; then
                    php -r '
                    $file = $argv[1];
                    $content = file_get_contents($file);

                    $content = preg_replace(
                        "/public function extendPostClassmap\(array \\\$classmap\): array\s*\{[^}]+\}/s",
                        "public function extendPostClassmap(array \$classmap): array\n\t{\n\t\t\$custom_classmap = [\n\t\t\t// Agregar mapeos de post types personalizados aquí\n\t\t];\n\n\t\treturn array_merge(\$classmap, \$custom_classmap);\n\t}",
                        $content
                    );

                    $content = preg_replace(
                        "/public function extendTermClassmap\(array \\\$classmap\): array\s*\{[^}]+\}/s",
                        "public function extendTermClassmap(array \$classmap): array\n\t{\n\t\t\$custom_classmap = [\n\t\t\t// Agregar mapeos de taxonomías personalizadas aquí\n\t\t];\n\n\t\treturn array_merge(\$classmap, \$custom_classmap);\n\t}",
                        $content
                    );

                    file_put_contents($file, $content);
                    ' "$starter_file" 2>/dev/null
                fi
                echo -e "${GREEN}✓${NC} Limpiado: TalampayaStarter.php"
            fi
        fi

        # Limpiar DefaultMenus.php
        local menus_file_rel="$THEME_SRC/Register/Menu/DefaultMenus.php"
        local menus_file="$PROJECT_ROOT/$menus_file_rel"
        if [[ -f "$menus_file" ]] && grep -q "projects" "$menus_file" 2>/dev/null; then
            local should_clean=true

            # Verificar si fue modificado respecto a upstream
            if is_modified_from_upstream "$menus_file_rel"; then
                if ! ask_auxiliary_file_action "$menus_file_rel" "Contiene definiciones de menús personalizados"; then
                    should_clean=false
                fi
            fi

            if [[ "$should_clean" == "true" ]]; then
                if [[ "$DRY_RUN" != "true" ]]; then
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
		];
	}
}
EOF
                fi
                echo -e "${GREEN}✓${NC} Limpiado: DefaultMenus.php"
            fi
        fi

        # Limpiar MenuContext.php
        local menu_context_rel="$THEME_SRC/Core/ContextExtender/Custom/MenuContext.php"
        local menu_context="$PROJECT_ROOT/$menu_context_rel"
        if [[ -f "$menu_context" ]] && grep -q "projects_menu" "$menu_context" 2>/dev/null; then
            local should_clean=true

            # Verificar si fue modificado respecto a upstream
            if is_modified_from_upstream "$menu_context_rel"; then
                if ! ask_auxiliary_file_action "$menu_context_rel" "Contiene contextos de menú personalizados"; then
                    should_clean=false
                fi
            fi

            if [[ "$should_clean" == "true" ]]; then
                if [[ "$DRY_RUN" != "true" ]]; then
                    cat > "$menu_context" << 'EOF'
<?php

namespace App\Core\ContextExtender\Custom;

use App\Core\ContextExtender\ContextExtenderInterface;
use App\Inc\Controllers\MenuController;

class MenuContext implements ContextExtenderInterface
{
	public function extendContext(array $context): array
	{
		$context["main_menu"] = MenuController::getPatternLabMenu("main");
		return $context;
	}
}
EOF
                fi
                echo -e "${GREEN}✓${NC} Limpiado: MenuContext.php"
            fi
        fi
    fi

    # Limpiar EndpointsManager.php (quitar GeolocationEndpoint si fue eliminado)
    local endpoints_manager_rel="$THEME_SRC/Core/Endpoints/EndpointsManager.php"
    local endpoints_manager="$PROJECT_ROOT/$endpoints_manager_rel"
    if [[ -f "$endpoints_manager" ]] && grep -q "GeolocationEndpoint" "$endpoints_manager" 2>/dev/null; then
        local should_clean=true

        # Verificar si fue modificado respecto a upstream
        if is_modified_from_upstream "$endpoints_manager_rel"; then
            if ! ask_auxiliary_file_action "$endpoints_manager_rel" "Contiene configuración de endpoints personalizados"; then
                should_clean=false
            fi
        fi

        if [[ "$should_clean" == "true" ]]; then
            if [[ "$DRY_RUN" != "true" ]]; then
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
		// Ejemplo: $this->addEndpoint(new MiEndpoint());
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
            fi
            echo -e "${GREEN}✓${NC} Limpiado: EndpointsManager.php"
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

    echo -e "${GREEN}Eliminados:${NC}  $DELETED_COUNT archivos"
    echo -e "${BLUE}Mantenidos:${NC}  $SKIPPED_COUNT archivos"
    echo -e "${CYAN}Fork:${NC}        ${#FORK_FILES[@]} archivos preservados"

    if [[ $DELETED_COUNT -gt 0 ]]; then
        echo ""
        echo -e "${CYAN}Próximos pasos:${NC}"
        echo "  1. Revisar los cambios: git status"
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

    # Verificar que estamos en un repositorio git
    if ! git -C "$PROJECT_ROOT" rev-parse --git-dir &>/dev/null; then
        echo -e "${RED}Error: No se encontró un repositorio git${NC}"
        exit 1
    fi

    if [[ "$DRY_RUN" == "true" ]]; then
        echo -e "${YELLOW}Modo DRY-RUN activado. No se realizarán cambios.${NC}"
        echo ""
    fi

    # Configurar upstream
    if ! setup_upstream; then
        echo -e "${RED}Error: No se pudo configurar upstream${NC}"
        exit 1
    fi
    echo ""

    # Obtener archivos de scaffolding desde upstream
    if ! get_upstream_scaffolding_files; then
        exit 1
    fi
    echo ""

    # Analizar proyecto
    analyze_project
    show_analysis_summary

    # Modo automático
    if [[ "$AUTO_YES" == "true" ]]; then
        delete_all_base
        clean_auxiliary_files
        clean_theme_references
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
            delete_all_base
            ;;
        "review_all")
            review_all
            ;;
        "delete_unmodified")
            delete_unmodified
            review_modified
            ;;
    esac

    # Limpiar archivos auxiliares
    clean_auxiliary_files

    # Limpiar referencias en theme
    clean_theme_references

    # Mostrar resumen
    show_summary
}

main "$@"
