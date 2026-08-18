<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PacienteController extends BaseCrudController
{
    protected string $modelo = Paciente::class;

    protected array $rolesEscritura = [User::ROL_ADMIN, User::ROL_DOCTOR];

    protected array $with = ['usuario', 'clinica', 'elegibilidadVigente'];

    protected ?string $columnaPaciente = 'id';

    protected array $filtrables = ['clinicaId', 'tipoDiabetes', 'sexo'];

    protected array $ordenables = ['id', 'created_at'];

    protected function reglas(Request $request, bool $creando): array
    {
        $req = $creando ? 'required' : 'sometimes';
        $id = $request->route('id');

        // Alta desde el panel: en vez de usuarioId puede venir `usuario` con los
        // datos personales y el backend crea la cuenta (rol paciente, sin
        // contrasena: el paciente entra por Auth0 y se vincula por correo).
        $conUsuarioAnidado = $creando && $request->has('usuario');
        $reqUsuario = $conUsuarioAnidado ? 'required' : 'sometimes';

        // Normalizar antes de validar: la regla unique compara literal y las
        // cuentas se guardan en minusculas (mismo criterio que Auth0Session).
        $email = $request->input('usuario.email');

        if (is_string($email)) {
            $request->merge(['usuario' => [...$request->input('usuario', []), 'email' => trim(mb_strtolower($email))]]);
        }

        // En actualizar, `usuario` edita el User ya ligado; el correo unico
        // ignora al propio usuario del paciente.
        $usuarioActualId = $creando ? null : Paciente::withTrashed()->whereKey($id)->value('usuarioId');

        return [
            'usuarioId' => $conUsuarioAnidado
                ? ['prohibited']
                : [$req, 'exists:users,id', Rule::unique('pacientes', 'usuarioId')->ignore($id)],
            'usuario' => ['sometimes', 'array'],
            'usuario.name' => [$reqUsuario, 'string', 'max:255'],
            'usuario.apellidoPaterno' => ['nullable', 'string', 'max:255'],
            'usuario.apellidoMaterno' => ['nullable', 'string', 'max:255'],
            'usuario.email' => [$reqUsuario, 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuarioActualId)],
            'usuario.telefono' => ['nullable', 'string', 'max:50'],
            'clinicaId' => ['nullable', 'exists:clinicas,id'],
            'fechaNacimiento' => [$req, 'date', 'before:today'],
            'sexo' => ['nullable', 'in:femenino,masculino,otro'],
            'tipoDiabetes' => [$req, 'string', 'max:255'],
            'pesoKg' => ['nullable', 'numeric', 'between:1,400'],
            'tallaCm' => ['nullable', 'integer', 'between:30,260'],
            'diagnosticadoEn' => ['nullable', 'date'],
            'alergias' => ['nullable', 'string'],
            'comorbilidades' => ['nullable', 'string'],
            'tabaquismo' => ['sometimes', 'boolean'],
            'contactoEmergencia' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function antesDeCrear(Request $request, array $datos): array
    {
        if (! isset($datos['usuario'])) {
            return $datos;
        }

        $usuario = User::create([
            ...$this->datosUsuario($datos['usuario']),
            'rol' => User::ROL_PACIENTE,
            'password' => null,
        ]);

        $this->auditar($request, 'crear', $usuario, null, $usuario->toArray());

        unset($datos['usuario']);
        $datos['usuarioId'] = $usuario->id;

        return $datos;
    }

    protected function antesDeActualizar(Request $request, Model $registro, array $datos): array
    {
        if (isset($datos['usuario'])) {
            /** @var Paciente $registro */
            $usuario = $registro->usuario;
            $antes = $usuario->toArray();

            $usuario->update($this->datosUsuario($datos['usuario']));

            $this->auditar($request, 'actualizar', $usuario, $antes, $usuario->toArray());
        }

        unset($datos['usuario']);

        return $datos;
    }

    /**
     * @param  array<string, mixed>  $usuario
     * @return array<string, mixed>
     */
    private function datosUsuario(array $usuario): array
    {
        if (isset($usuario['email'])) {
            // Mismo criterio que Auth0SessionController: minusculas para que el
            // login por Auth0 encuentre esta cuenta y no cree otra.
            $usuario['email'] = trim(mb_strtolower($usuario['email']));
        }

        return array_intersect_key($usuario, array_flip(['name', 'apellidoPaterno', 'apellidoMaterno', 'email', 'telefono']));
    }
}
