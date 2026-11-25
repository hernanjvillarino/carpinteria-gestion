<?php

// Mensajes en español para las reglas que usa el sistema. Las que falten caen al inglés.
return [
    'array' => 'El campo :attribute no es válido.',
    'boolean' => 'El campo :attribute tiene que ser sí o no.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'email' => 'El campo :attribute tiene que ser un email válido.',
    'enum' => 'El valor elegido en :attribute no es válido.',
    'exists' => 'El valor elegido en :attribute no es válido.',
    'image' => 'El archivo :attribute tiene que ser una imagen.',
    'in' => 'El valor elegido en :attribute no es válido.',
    'in_array' => 'El campo :attribute tiene que estar en :other.',
    'integer' => 'El campo :attribute tiene que ser un número entero.',
    'max' => [
        'array' => 'El campo :attribute no puede tener más de :max elementos.',
        'file' => 'El archivo :attribute no puede pesar más de :max kilobytes.',
        'numeric' => 'El campo :attribute no puede ser mayor a :max.',
        'string' => 'El campo :attribute no puede tener más de :max caracteres.',
    ],
    'min' => [
        'string' => 'El campo :attribute tiene que tener al menos :min caracteres.',
    ],
    'password' => [
        'min' => 'La contraseña tiene que tener al menos :min caracteres.',
    ],
    'required' => 'El campo :attribute es obligatorio.',
    'required_if' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute tiene que ser texto.',
    'unique' => 'Ya existe un registro con ese :attribute.',
    'uploaded' => 'No se pudo subir :attribute.',

    'attributes' => [
        'name' => 'nombre',
        'email' => 'email',
        'phone' => 'teléfono',
        'password' => 'contraseña',
        'role' => 'rol',
        'title' => 'título',
        'client_id' => 'cliente',
        'stage_id' => 'etapa',
        'priority' => 'prioridad',
        'estimated_delivery' => 'entrega estimada',
        'comment' => 'comentario',
        'photos' => 'fotos',
        'photos.*' => 'foto',
        'carpinteros.*' => 'carpintero',
        'gated_community_id' => 'barrio',
    ],
];
