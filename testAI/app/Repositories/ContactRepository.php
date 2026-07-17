<?php

namespace App\Repositories;

use App\Models\ContactRequest;

class ContactRepository
{
    public function create(array $data): ContactRequest
    {
        return ContactRequest::create($data);
    }

    public function countSince(\DateTimeInterface $since): int
    {
        return ContactRequest::where('created_at', '>=', $since)->count();
    }
}
