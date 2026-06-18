---
policy: drupal-security-extensions
version: 1.0
layer: 1
technology: drupal
extends: owasp-top10
updated: 2026-06-18
---

# Drupal Security Extensions — Política de Plataforma (Capa 1)

> **Política de Capa 1.**
> Extiende `core/owasp-top10.md` con implementaciones específicas de Drupal 11.
> No reemplaza OWASP — ambas aplican. Los proyectos no pueden excluir esta política,
> pero sí pueden agregar restricciones adicionales en su Capa 2.

## Cómo leer esta política

Cada sección de OWASP tiene su traducción a APIs y patrones de Drupal 11.
El agente debe aplicar `core/owasp-top10.md` primero y luego esta extensión.

---

## A01 — Broken Access Control en Drupal

### Verificación de permisos con Entity API

```php
// SIEMPRE: verificar antes de retornar o modificar
$node = Node::load($node_id);
if (!$node || !$node->access('view')) {
  throw new AccessDeniedHttpException();
}

// En REST Resources: usar el parámetro de anotación
// @DCG o verificación explícita en el método handle()
```

**En REST Resources** — la capa de Resource es solo HTTP. El permiso se verifica en la anotación `@RestResource` (campo `permission`) Y en el Service si la lógica lo requiere:

```php
// En routing.yml o anotación — nivel mínimo
'_permission' => 'access content'

// En el Service — nivel de entidad específica
if (!$entity->access('view', $this->currentUser)) {
  throw new AccessDeniedHttpException('Acceso denegado al recurso solicitado.');
}
```

**En Forms y Controllers:**

```php
// Form: usar #access en el render array
$form['admin_field'] = [
  '#access' => $this->currentUser->hasPermission('administer site configuration'),
];
```

**Señales de fallo específicas de Drupal:**
- `Node::load()` sin `->access()` posterior en código custom
- REST Resources sin `permission` en la anotación
- `\Drupal::currentUser()->isAuthenticated()` como ÚNICA verificación (no es suficiente)
- Routes sin `_permission`, `_role` o `_entity_access` en `requirements:`

---

## A02 — Cryptographic Failures en Drupal

### Secrets en Drupal

```php
// MALO: hardcoded
$key = 'sk_live_abc123_hardcoded';

// BUENO: via config system (se puede cifrar con Key module)
$key = \Drupal::config('mi_modulo.settings')->get('api_key');

// BUENO: via variable de entorno
$key = getenv('STRIPE_API_KEY');
// O via settings.php:
// $config['mi_modulo.settings']['api_key'] = getenv('STRIPE_API_KEY');
```

### Passwords de usuarios

Drupal maneja el hash de passwords automáticamente con `PhpassHashedPassword`.
No implementar hashing de passwords manualmente — usar la API de usuarios de Drupal.

**Señales de fallo específicas de Drupal:**
- `md5()` o `sha1()` aplicados a passwords en código custom
- Config con secrets en `config/sync/` (se commitean al repo)
- `$settings['hash_salt']` con un valor predecible o vacío en `settings.php`

---

## A03 — Injection en Drupal

### Database API (el único lugar correcto: src/Repository/)

```php
// MALO: query raw con concatenación (además viola separación de capas)
\Drupal::database()->query("SELECT * FROM node WHERE title = '$title'");

// BUENO: Database API con placeholders
$query = \Drupal::database()->select('node_field_data', 'n')
  ->fields('n', ['nid', 'title'])
  ->condition('n.title', $title)    // automáticamente prepared
  ->condition('n.status', 1);

// BUENO para queries complejas: placeholders explícitos
\Drupal::database()->query(
  'SELECT * FROM {node_field_data} WHERE title = :title',
  [':title' => $title]
);
```

**Verificar en el agente:**
- ¿Toda la lógica de base de datos está en `src/Repository/`?
- ¿Hay `->query()`, `->select()`, `->insert()` fuera de Repository? → doble hallazgo (separación de capas + seguridad)
- ¿Hay algún `db_query()` (deprecated)? → hallazgo crítico

### Output / XSS en Twig

Twig escapa automáticamente. Los únicos riesgos:
```twig
{# MALO: deshabilita el escape #}
{{ content | raw }}

{# BUENO: escapado automático #}
{{ content }}

{# Para markup confiable (de Drupal, no del usuario): #}
{{ content | render }}
```

---

## A05 — Security Misconfiguration en Drupal

### Settings.php en producción

```php
// settings.php de producción DEBE tener:
$config['system.logging']['error_level'] = 'hide';  // nunca 'verbose' en prod

// NUNCA en producción:
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
```

### Módulos de desarrollo

Los siguientes módulos NO deben estar activados en producción:
- `devel`, `devel_generate`
- `kint`, `webprofiler`
- `stage_file_proxy` (solo dev/staging)

**Señales de fallo:**
- `devel` en el listado de módulos activos en `config/sync/core.extension.yml`
- `$config['system.logging']['error_level'] = 'verbose'` en settings.php

---

## Checklist adicional Drupal

Además de los 10 puntos de OWASP, verificar siempre en código Drupal:

- [ ] Los endpoints REST tienen `_format` restringido (no abierto a cualquier formato)
- [ ] Los archivos subidos van a `private://` si son sensibles, no a `public://`
- [ ] Las custom entities tienen `access()` en sus anotaciones
- [ ] Los form handlers validan el token CSRF (`$form_state->isSubmitted()`)
- [ ] Los hooks no ejecutan queries sin pasar por Repository
- [ ] Las dependencias en `composer.json` tienen `composer audit` en 0 vulnerabilidades altas/críticas

---

## Separación de capas como requisito de seguridad

La arquitectura en capas (Resource/Controller → Service → Repository) no es solo una
convención de código — es un control de seguridad. Código fuera de esta separación es
también un hallazgo de seguridad porque:

1. Dificulta la auditoría (la lógica está dispersa)
2. Permite que la capa HTTP ejecute queries sin pasar por validaciones del Service
3. Imposibilita el testing unitario de la lógica de autorización

Si el security-reviewer encuentra queries en Resource, Controller o Form: reportar como
ALTO (separación de capas violada + riesgo de seguridad por falta de auditoría).
