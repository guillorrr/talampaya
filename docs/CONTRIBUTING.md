# 🧭 Guía de Contribución – Convenciones para Clases y Métodos

Este proyecto sigue una arquitectura basada en PSR-4 y uso modular de clases, helpers, servicios, controladores y modelos. Para mantener la consistencia, claridad y mantenibilidad del código, seguí las siguientes convenciones.

---

## 📦 Estructura general

- Las clases se organizan por namespace, según carpeta:
  - `Theme\App\`
  - `Theme\Core\`
  - `Theme\Helpers\`
  - `Theme\Services\`
  - `Theme\Utils\`
- Se usa autoloading mediante Composer (`composer.json`)

---

## 🧱 Convenciones de clases

### Clases tipo `Helper` o `Util`
- Deben ser **estáticas**
- No deben tener propiedades internas (`$this`)
- Solo contienen funciones puras o wrappers de funciones nativas/WP

```php
namespace Theme\Helpers;

class JsonHelper {
    public static function decode(string $json): array {
        return json_decode($json, true);
    }
}
```

---

### Clases tipo `Service`
- Pueden tener propiedades internas
- Se instancian normalmente (`new Servicio()`)
- Pueden usar inyección de dependencias en el constructor

```php
namespace Theme\Services;

class NewsletterService {
    private Mailer $mailer;

    public function __construct(Mailer $mailer) {
        $this->mailer = $mailer;
    }

    public function send(string $email): void {
        $this->mailer->send($email);
    }
}
```

---

### Clases tipo `Controller`
- Se usan para manejar flujos específicos (API, bloques, acciones)
- Se cargan desde hooks o instancias

---

## 🔓 Modificadores de visibilidad

| Tipo      | Uso recomendado                          |
|-----------|------------------------------------------|
| `public`  | Métodos que se usan desde otras clases   |
| `private` | Métodos internos que no deben exponerse  |
| `protected` | Solo si esperás extender la clase      |

---

## ⚡ Métodos estáticos (`static`)

Usá `static` cuando:

- La clase no necesita `$this` ni propiedades
- El método se puede usar sin instanciar la clase
- Ideal para Helpers o funciones puras

---

## ⛔ Cuándo **no** usar `static`

Evitalo si:

- El método depende de propiedades internas
- Vas a extender la clase y sobrescribir comportamiento
- Necesitás manejar configuración o estado (`$this->config`)

---

## 🧪 Ejemplo: Comparación

### ✔️ Correcto (static helper)

```php
JsonHelper::decode($json);
```

### ❌ Incorrecto (instancia innecesaria)

```php
$helper = new JsonHelper();
$helper->decode($json);
```

---

## ✅ Buenas prácticas

- Siempre usar `namespace` correctamente
- Usar `use` para importar clases
- Evitar funciones globales si pueden encapsularse
- No repetir lógica (DRY)
- Documentar métodos con `@param`, `@return`, `@throws` si aplica

---

Gracias por mantener la calidad del proyecto 🚀