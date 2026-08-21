<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role, oprávnění a jejich přiřazení.
 *
 * Seeder je idempotentní — oprávnění vznikají přes findOrCreate() a rolím se
 * přiřazují přes syncPermissions(), takže opakovaný běh sjednotí stav s maticí
 * níže (včetně odebrání práva, které z matice zmizelo).
 *
 * POZOR na super_admina v matici — NEODSTRAŇOVAT jako duplicitu.
 *
 * config/filament-shield.php není publikovaný, takže platí vendor defaulty:
 * super_admin.enabled = true, define_via_gate = false. Při define_via_gate =
 * false Shield gate vůbec neregistruje — FilamentShieldServiceProvider ho
 * zapíná jen ve větvi Utils::isSuperAdminDefinedViaGate(), takže nastavení
 * intercept_gate = 'before' je v tomto configu mrtvé a na nic se nespoléhá.
 *
 * Shield místo toho super_adminovi oprávnění explicitně přiřazuje při
 * shield:generate (FilamentShield::giveSuperAdminPermission). Zdrojem pravdy
 * jsou tedy permission řádky, ne gate bypass — a proto super_admin musí být
 * v matici vyjmenovaný. Bez toho se do administrace nedostane vůbec.
 */
class RoleSeeder extends Seeder
{
    /** Resourcy s plnou sadou akcí. */
    private const RESOURCES = [
        'article',
        'course',
        'course::type',
        'faq',
        'gallery',
        'partner',
        'trainer',
        'user',
    ];

    /** Plná sada akcí, kterou Shield generuje pro běžný resource. */
    private const ACTIONS = [
        'view',
        'view_any',
        'create',
        'update',
        'delete',
        'delete_any',
        'force_delete',
        'force_delete_any',
        'restore',
        'restore_any',
        'replicate',
        'reorder',
    ];

    /** Správa obsahu bez trvalého mazání — smazané jde vždy obnovit z koše. */
    private const ACTIONS_WITHOUT_FORCE_DELETE = [
        'view',
        'view_any',
        'create',
        'update',
        'delete',
        'delete_any',
        'restore',
        'restore_any',
        'replicate',
        'reorder',
    ];

    /** Jen čtení. */
    private const READ_ONLY = [
        'view',
        'view_any',
    ];

    /** Psaní článků bez mazání — mazání obsahu patří člověku, ne agentovi. */
    private const ARTICLE_WRITER = [
        'view',
        'view_any',
        'create',
        'update',
    ];

    /**
     * Resource 'role' má užší sadu — Shield u něj negeneruje
     * force_delete/restore/replicate/reorder.
     */
    private const ROLE_RESOURCE_ACTIONS = [
        'view',
        'view_any',
        'create',
        'update',
        'delete',
        'delete_any',
    ];

    public function run(): void
    {
        $this->createPermissions();

        // Spatie si oprávnění cachuje a invaliduje cache přes model eventy —
        // ty ale DatabaseSeeder umlčuje (WithoutModelEvents). Bez ručního resetu
        // by syncPermissions() právě založená oprávnění nenašel a spadl na
        // PermissionDoesNotExist.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->matrix() as $roleName => $resources) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->syncPermissions($this->permissionNames($resources));
        }
    }

    /**
     * Založí všech 102 oprávnění, i ta, která žádná role z matice nedostane.
     * Musí existovat, aby je Shield uměl nabídnout v administraci.
     */
    private function createPermissions(): void
    {
        foreach (self::RESOURCES as $resource) {
            foreach (self::ACTIONS as $action) {
                Permission::findOrCreate($action.'_'.$resource, 'web');
            }
        }

        foreach (self::ROLE_RESOURCE_ACTIONS as $action) {
            Permission::findOrCreate($action.'_role', 'web');
        }
    }

    /**
     * Matice oprávnění: role → resource → povolené akce.
     *
     * Co tady není, role nemá. Nový resource se přidá dopsáním řádku
     * ke každé roli, která k němu má mít přístup.
     *
     * @return array<string, array<string, array<int, string>>>
     */
    private function matrix(): array
    {
        return [
            // Správce systému — všechno včetně rolí a oprávnění.
            // Musí tu být explicitně, viz komentář u třídy.
            'super_admin' => [
                'article' => self::ACTIONS,
                'faq' => self::ACTIONS,
                'gallery' => self::ACTIONS,
                'partner' => self::ACTIONS,
                'trainer' => self::ACTIONS,
                'course' => self::ACTIONS,
                'course::type' => self::ACTIONS,
                'user' => self::ACTIONS,
                'role' => self::ROLE_RESOURCE_ACTIONS,
            ],

            // Klient — plná správa obsahu i uživatelů. Role a oprávnění nespravuje.
            'admin' => [
                'article' => self::ACTIONS,
                'faq' => self::ACTIONS,
                'gallery' => self::ACTIONS,
                'partner' => self::ACTIONS,
                'trainer' => self::ACTIONS,
                'course' => self::ACTIONS,
                'course::type' => self::ACTIONS,
                'user' => self::ACTIONS,
            ],

            // Sekretariát — stejný obsah jako admin, ale bez trvalého mazání
            // a bez přístupu k uživatelům.
            'super_redaktor' => [
                'article' => self::ACTIONS_WITHOUT_FORCE_DELETE,
                'faq' => self::ACTIONS_WITHOUT_FORCE_DELETE,
                'gallery' => self::ACTIONS_WITHOUT_FORCE_DELETE,
                'partner' => self::ACTIONS_WITHOUT_FORCE_DELETE,
                'trainer' => self::ACTIONS_WITHOUT_FORCE_DELETE,
                'course' => self::ACTIONS_WITHOUT_FORCE_DELETE,
                'course::type' => self::ACTIONS_WITHOUT_FORCE_DELETE,
            ],

            // Externí přispěvatelé a AI agenti — píší články, zbytek jen čtou,
            // aby v článku mohli odkázat na kurz nebo trenéra.
            'redaktor' => [
                'article' => self::ARTICLE_WRITER,
                'faq' => self::READ_ONLY,
                'gallery' => self::READ_ONLY,
                'partner' => self::READ_ONLY,
                'trainer' => self::READ_ONLY,
                'course' => self::READ_ONLY,
                'course::type' => self::READ_ONLY,
            ],
        ];
    }

    /**
     * Přeloží řádek matice na plochý seznam názvů oprávnění.
     *
     * @param  array<string, array<int, string>>  $resources
     * @return array<int, string>
     */
    private function permissionNames(array $resources): array
    {
        $names = [];

        foreach ($resources as $resource => $actions) {
            foreach ($actions as $action) {
                $names[] = $action.'_'.$resource;
            }
        }

        return $names;
    }
}
