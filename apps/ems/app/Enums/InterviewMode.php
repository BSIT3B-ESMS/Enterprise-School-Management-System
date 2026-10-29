<?php

namespace App\Enums;

enum InterviewMode: string
{
    case FaceToFace = 'face_to_face';
    case Online = 'online';

    public function label(): string
    {
        return match ($this) {
            self::FaceToFace => 'Face to face',
            self::Online => 'Online',
        };
    }
}
