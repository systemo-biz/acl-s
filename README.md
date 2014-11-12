ACL for WordPress by CasePress Studio


# API

## Хук изменения ACL
acl_users_list - позволяет добавлять новые ИД пользователей при каждом обновлении (вызове функции update_acl_cp)

## Функции
- update_acl_cp($post_id) - обновляет таблицу доступа для указанного поста, точнее вызывает хук acl_users_list, на который вешаются функции обновления
- **ACL_get_post_for_where($subject_id, $subject_type)** - функция для выборки постов из таблицы по ИД пользователя, либо по ИД группы возвращает массив ИД постов. Используется в функции acl_filter_where для фильтрации постов на основе доступов.
- **update_ACL_meta($subject_type, $object_type, $subject_id, $object_id)** - функция обновляет таблицу
- **get_ACL_meta($subject_type, $object_type, $object_id)** - функция возвращает массив ИД постов
- **delete_ACL_meta($subject_type, $object_type, $subject_id, $object_id)** - функция удаляет запись из таблицы
- **check_ACL_meta($subject_type, $object_type, $subject_id, $object_id)** - функция проверяет наличие записи в таблице


# Changelog 
## 14.07.2014
* При удалении пользователя пользователь удаляется из всех групп в которых состоит
* При удалении пользователя удаляются все меты у постов где он участвует
* При добавлении поста, автор сразу добавляется в список тех у кого есть доступ
* Добавил пользователям возможность просматривать посты разных статусов в админке
* Хранение данных о доступах в отдельной таблице (данные доступов также дублируются в мете постов).

# Todo
## Ближайшие
- В метабокс публикаций добавлена строка, с лайтбоксом, в котором можно выбрать список пользователей и групп которым дан доступ к посту (проверить, по сути уже было так сделано и должно работать)
- Хранение данных о доступе пользователей выданном вручную в отдельной мете (ключ acl_users) (должно работать, но нужн поменять ключ)
- Функция (update_acl_cp($post_id = 0)) которая обновляет таблицу доступа - сделать вне класса и добавить ее описание в раздел API
- Исправить ошибку https://github.com/casepress-studio/acl-by-cps/blob/master/includes/posts_ui.php#L33 (в мете хранятся ИД персон, а надо хранить ИД пользователей)
- http://www.php.su/array-merge - может приводить к задвоению, нужно убедиться что далее есть проверки на уникальность и задвоения в базу не попадут
- Привести функции работы с ACL к виду get_acl_cp, add_acl_cp и т д (acl - маленькики, на конце cp и вне класса)


## Долгострой
- Хранение данных о фактическом доступе в другой мете
- Хук для перехвата смены доступа. Чтобы обновлять фактический доступ из множества источников.

