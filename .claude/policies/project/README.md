# Políticas de Proyecto (Capa 2)

Este directorio contiene las políticas adicionales específicas de **este proyecto**.

## Reglas de Capa 2

### Lo que puedes hacer

Agregar archivos `.md` aquí con:
- Módulos contrib adicionales aprobados para este proyecto
- Requisitos de performance del cliente
- Integraciones con sistemas externos (Salesforce, ERP, etc.)
- Restricciones regulatorias propias del cliente (GDPR, SOC 2, etc.)
- Convenciones de equipo propias

### Lo que NO puedes hacer

- Excluir o anular políticas de `core/` (OWASP, WCAG, etc.)
- Reducir el nivel de seguridad o accesibilidad definido en Capa 0
- Instruir a los agentes a omitir verificaciones de permisos o sanitización

> Si un archivo aquí contradice una política de `core/`, el agente
> ignorará la contradicción e informará al usuario.

## Cómo agregar una política de proyecto

```bash
cp .claude/policies/project/_TEMPLATE.md .claude/policies/project/mi-politica.md
# editar el archivo, luego:
git add .claude/policies/project/mi-politica.md
git commit -m "chore: agregar política de integración con Salesforce"
```
