# SGF – Backend (Symfony + Docker Compose)

API REST para el **ABM de Programas, Actividades, Encuentros y Comisiones**, junto con los **nomencladores** de **Tipo de Actividad** y **Modalidad de Encuentro**.  
El backend está desarrollado en **Symfony** y se levanta con **Docker Compose**.

---

## ✨ Objetivos

- Gestionar entidades principales:
  - **Programas**, **Actividades**, **Encuentros**, **Comisiones**
- Mantener nomencladores:
  - **Tipo de Actividad**, **Modalidad de Encuentro**
- Exponer endpoints REST con filtros, paginación y validaciones.

---

## 🧱 Stack técnico

- **PHP** 8.x · **Symfony** 6/7  
- **MySQL 8**  
- **Apache (PHP-Apache)**  
- **Doctrine ORM**, **Symfony Validator**, **Serializer Groups**  
- **KNP Paginator**  
- **Docker** + **Docker Compose**

> Patrón de capas: **Controller → Manager → Repository** con **DTOs** para validación/entrada y **Serializer Groups** para respuestas (`list`/`detail`).

---

### Requisitos
- Docker + Docker Compose


