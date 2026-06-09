# Quality Gates Determinísticos

Guía completa de validaciones automáticas y cómo usarlas en desarrollo diario.

## Resumen de gates

| Gate | Tool | Cuando | Comando | Falla en |
|------|------|--------|---------|----------|
| Composer | composer validate | Pre-commit | `composer lint:composer` | JSON inválido |
| PHP Syntax | php -l | Pre-commit | `composer lint:php` | Parse errors |
| PHPCS | phpcodesniffer | Pre-commit | `composer lint:phpcs` | Style violations |
| PHPStan | phpstan | Pre-merge | `composer lint:phpstan` | Type errors |
| PHPUnit | phpunit | Pre-merge | `composer test` | Tests failed, <70% coverage |
| TwigCS | twigcs | Pre-commit (GrumPHP) | `composer lint:twig` | Twig violations |
| Debug code | grep | Pre-commit | GrumPHP | die(), var_dump(), etc |
| Merge markers | grep | Pre-commit | GrumPHP | <<<<<<, =====, >>>>>> |
| Commit message | format validation | Pre-commit | GrumPHP | Spanish, capital first letter |

## Pre-commit gates (bloquean commit)

### 1. Composer validation

```bash
composer lint:composer
```

**Qué valida**:
- JSON válido
- Dependencias versión correcta
- Scripts definidos

**Fail si**:
```
[ErrorException] Parse error in composer.json
```

**Fix**:
```bash
# Ver error
composer validate

# Corregir JSON
vim composer.json
```

### 2. PHP Syntax

```bash
composer lint:php
```

**Qué valida**:
- `<?php ?>` válido
- Sintaxis correcta
- No caracteres inválidos

**Fail si**:
```
Parse error: syntax error, unexpected 'echo' (T_ECHO)
```

**Fix**:
```bash
# Identificar archivo
composer lint:php

# Corregir
vim web/modules/custom/mi_modulo/src/Service/Mi.php
```

### 3. PHPCS (PHP Code Sniffer)

```bash
composer lint:phpcs
```

**Qué valida**:
- PSR12 estándar
- Drupal coding conventions
- Naming conventions

**Fail si**:
```
ERROR | Line indentation incorrect; expected 2 spaces, found 4
ERROR | Missing space after comma
ERROR | Variable name must start with a dollar sign
```

**Fix manual**:
```bash
# Ver errores
composer lint:phpcs

# Corregir archivo específico
vim web/modules/custom/mi_modulo/src/Service/Mi.php

# Auto-fijar
composer fix
```

**Errores comunes**:

| Error | Causa | Fix |
|-------|-------|-----|
| `Indentation incorrect` | Tabs vs spaces | Use 2 spaces |
| `Missing space after comma` | `$a,$b` | Change to `$a, $b` |
| `Missing @param` | Docblock incompleto | Add `@param` for each arg |
| `Variable $name should follow` | `$myVar` vs `$my_var` | Use snake_case |

### 4. GrumPHP Pre-commit hooks

```bash
git commit -m "feat: mi cambio"
```

GrumPHP automatically runs:

**a) Debug code detection**

Bloqueado:
```php
die();
var_dump($data);
syslog('msg');
print_r($array);
dpm($data);  // Drupal dev module
```

```twig
{{ debug() }}
```

**Fix**:
```bash
# Buscar debug code
grep -r "die\|var_dump\|print_r\|dpm\|syslog" web/modules/custom/

# Remover o cambiar a logging
// BAD
var_dump($campaign);

// GOOD
\Drupal::logger('campaigns')->debug('Campaign: %data', ['%data' => json_encode($campaign)]);
```

**b) Merge conflict markers**

Bloqueado:
```
<<<<<<< HEAD
my changes
=======
their changes
>>>>>>> branch
```

**Fix**:
```bash
# Resolver conflicto
git status  # Ver conflicting files

# Editar archivo
vim web/modules/custom/campaigns/campaigns.module

# Re-add después de resolver
git add web/modules/custom/campaigns/campaigns.module

# Retry commit
git commit -m "feat: mi cambio"
```

**c) Commit message format**

Validación:
- Debe empezar con mayúscula
- Formato: `Type: descripción` (o simplemente `Descripción`)
- Máximo 80 caracteres subject
- 72 caracteres body

**OK**:
```
feat: crear módulo campaigns

Descripción más larga aquí si es necesario.
```

**NO OK**:
```
fix: cambio de bug  # lowercase 'fix'
crear module nuevo  # without type
```

**Fix**:
```bash
# Reescribir commit
git commit --amend -m "Fix: cambio de bug"

# Retry
git commit
```

## Pre-merge gates (validación antes de mergear)

### 1. PHPStan (Static Analysis)

```bash
composer lint:phpstan
```

**Qué valida** (Nivel 5):
- Tipos declarados
- Métodos que no existen
- Parámetros tipo incorrecto
- Propiedades inválidas

**Fail si**:
```
Parameter #1 $campaign of method expects CampaignInterface but mixed given
Method doSomething() does not exist in class Campaign
```

**Fix**:
```bash
# Ver errores con líneas
composer lint:phpstan

# Solución 1: Agregar tipo
// BAD
public function process($data) {}

// GOOD
public function process(CampaignInterface $campaign): void {}

# Solución 2: Ignorar temporalmente (último recurso)
// @phpstan-ignore-next-line
$result = $this->unsafeMethod();
```

### 2. PHPUnit Tests

```bash
composer test
```

**Qué valida**:
- Tests pasan
- Coverage >= 70%

**Fail si**:
```
FAILURES!
Tests run: 10, Failures: 1
Code Coverage: 65% (< 70% required)
```

**Fix**:

```bash
# Ejecutar tests con output
composer test

# Ver coverage
composer test -- --coverage-html=coverage
# Abre coverage/index.html en browser

# Agregar tests faltantes
vim tests/src/Unit/CampaignServiceTest.php

// Ejemplo: Test para método no testeado
public function testCalculateTotal() {
  $service = new CampaignService();
  $total = $service->calculateTotal(100, 50);
  $this->assertEquals(150, $total);
}
```

### 3. Twig Templates

```bash
composer lint:twig
```

**Qué valida**:
- Twig válido
- Indentation correcta
- Spacing

**Fail si**:
```
Twig syntax error at line 5, column 10
```

**Fix**:
```bash
# Ver error
composer lint:twig

# Corregir
vim web/themes/custom/mi_tema/templates/nodo.html.twig

// Ejemplo
{{ campaign.name|upper }}  // Correcto
{{ campaign.name|upper }}  // Correcto
```

## Validación combinada

### Antes de hacer commit

```bash
# Auto-fijar lo que se puede
composer fix

# Validar todo
composer qa

# Si todo OK
git add .
git commit -m "feat: mi cambio"

# Si hay errores, corregir y retry
```

### Antes de hacer PR

```bash
# Tests
composer test

# Análisis estático
composer lint:phpstan

# Revisión manual
composer lint:phpcs

# Twig
composer lint:twig
```

### Checklist de PR

```markdown
- [ ] composer qa pasa
- [ ] composer test pasa (70%+ coverage)
- [ ] Documentación actualizada
- [ ] Tests nuevos agregados
- [ ] No hay debug code
- [ ] Commits messages en español con capital
- [ ] Security Review completado
- [ ] Accessibility Review completado (si aplica)
```

## Configuración personalizada

### Cambiar strictness

En `CLAUDE.md`:

```yaml
agents:
  code-reviewer:
    phpstan_level: 5        # 1-9, default 5
    test_coverage_min: 70   # %, default 70
    
  security-reviewer:
    strictness: "high"      # low, medium, high
```

### Ignorar reglas específicas

PHPStan:
```php
// @phpstan-ignore-line
$unsafe = call_undefined_method();

// @phpstan-ignore-next-line
$data = $this->unknownMethod();
```

PHPCS:
```php
// phpcs:disable Generic.Formatting.SpaceAfterCast
$int = ( int ) $value;
// phpcs:enable Generic.Formatting.SpaceAfterCast
```

### Excluir archivos

En `phpcs.xml`:
```xml
<exclude-pattern>vendor/</exclude-pattern>
<exclude-pattern>*/tests/*</exclude-pattern>
```

## Troubleshooting

### "PHPCS: Referenced sniff 'SlevomatCodingStandard' does not exist"

```bash
# Cause: Missing installed_paths
# Fix: Run lint script
composer lint:php

# Script configura installed_paths automáticamente
```

### "PHPStan: Path does not exist"

```bash
# Cause: Empty directory
# Fix: Create .gitkeep
touch web/modules/custom/.gitkeep
touch web/themes/custom/.gitkeep

# O use wrapper script
composer lint:phpstan
```

### "GrumPHP blocks commit"

```bash
# Ver qué falló
git status

# Corregir
composer fix
composer test

# Retry
git add .
git commit -m "feat: mi cambio"
```

### "Test coverage < 70%"

```bash
# Ver qué no está testeado
composer test -- --coverage-html=coverage
# Abre coverage/index.html

# Agregar tests
vim tests/src/Unit/...
vim tests/src/Functional/...

# Rerun
composer test
```

## Referencias

- [PHPCS Rules](https://github.com/squizlabs/PHP_CodeSniffer/wiki/Annotated-ruleset.xml)
- [PHPStan Documentation](https://phpstan.org/)
- [PHPUnit for Drupal](https://www.drupal.org/docs/automated-testing/phpunit)
- [PSR-12](https://www.php-fig.org/psr/psr-12/)
