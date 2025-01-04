<?php
namespace App\Imports;

use App\Models\User;
use App\Http\Enum\RoleSystemEnum;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Validation\Rule;

class UsersImport implements ToModel, WithHeadingRow, WithValidation
{
    protected $createdBy;
    protected $defaultPassword = 'password123';

    public function __construct($createdBy)
    {
        $this->createdBy = $createdBy;
    }

    private function getAllRoles(): array
    {
        return [
            RoleSystemEnum::ADMIN,
            RoleSystemEnum::RECEPTIONIST,
            RoleSystemEnum::DOCTOR
        ];
    }

    public function model(array $row)
    {
        // Handle password: if empty or invalid, use default password
        $password = !empty($row['password']) ? (string)$row['password'] : $this->defaultPassword;
        
        return new User([
            'name' => trim($row['name']),
            'email' => trim($row['email']),
            'password' => Hash::make($password),
            'phone' => isset($row['phone']) ? trim((string)$row['phone']) : null,
            'role' => strtolower($row['role'] ?? RoleSystemEnum::RECEPTIONIST),
            'created_by' => $this->createdBy,
        ]);
    }

    public function rules(): array
    {
        return [
            '*.name' => 'required|string|max:255',
            '*.email' => 'required|email|unique:users,email',
            '*.phone' => 'nullable',
            '*.role' => ['nullable', Rule::in($this->getAllRoles())],
            // Remove password validation since we'll handle it in the model method
            '*.password' => 'nullable'
        ];
    }

    public function customValidationMessages()
    {
        return [
            '*.name.required' => 'The name field is required.',
            '*.email.required' => 'The email field is required.',
            '*.email.email' => 'The email must be a valid email address.',
            '*.email.unique' => 'The email has already been taken.',
            '*.role.in' => 'The role must be one of: ' . implode(', ', $this->getAllRoles())
        ];
    }
}