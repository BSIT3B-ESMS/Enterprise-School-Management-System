<?php

namespace Database\Seeders;

use App\Enums\MaritalStatus;
use App\Enums\Sex;
use Illuminate\Support\Str;

/**
 * Invented personal details for seeded employees. The names on the faculty roster are real, but everything made up here
 * (sex guessed from the first name, hire date, birthdate, civil status, contacts, and ID numbers) is synthetic.
 */
final class SyntheticProfile
{
    /**
     * First names (the first word of a given name) that read as female or male. A name not listed falls back to a spelling
     * guess; the two unsure ones (Dionimar and Riegie) are set to male.
     */
    private const FEMALE = [
        'maila', 'cecile', 'anna', 'eva', 'magdalena', 'marlyn', 'maria', 'realiza', 'marisol', 'lorna', 'michelle', 'rhea', 'charm',
        'glady', 'rosario', 'jennifer', 'rochelle', 'bernadette', 'sheryl', 'carmelita', 'kristine', 'jasmin', 'karen', 'sherlyn',
        'rebecca', 'noreen', 'laura', 'racquel', 'greta', 'catherine', 'ruby', 'janice', 'janeth', 'samantha', 'carolyn', 'marilyn',
        'carmina', 'lea', 'marjorie', 'elene', 'jessica', 'marife', 'eureka', 'rowena', 'bellie', 'khressa', 'jovid', 'lourdes',
        'corazon', 'perla',
    ];

    private const MALE = [
        'daniel', 'dionimar', 'rommel', 'andy', 'joel', 'raymond', 'godofredo', 'alberto', 'darwin', 'jonathan', 'michael', 'antonio',
        'romeo', 'eugene', 'vincent', 'riegie', 'rodolfo', 'federico', 'randy', 'juanito', 'john', 'jayson', 'joseph', 'alexen',
        'norman', 'eliseo', 'ramil', 'gerald', 'raymundo', 'mark', 'fidel', 'benito', 'rajesh', 'enrique', 'daryl', 'june', 'roderick',
        'jojo', 'jerome', 'vonjordan', 'benedict', 'emmanuel',
    ];

    private const STREETS = ['Shaw Boulevard', 'Ortigas Avenue', 'Kapitolyo', 'Caniogan', 'Santolan', 'Rosario', 'Manggahan', 'Bambang', 'Pineda', 'Sagad'];

    private const FIRST_NAMES = ['Maria', 'Jose', 'Ana', 'Juan', 'Rosa', 'Pedro', 'Luz', 'Carlos', 'Elena', 'Ramon', 'Teresa', 'Ricardo'];

    private const LAST_NAMES = ['Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Torres', 'Flores', 'Aquino', 'Navarro', 'Mercado'];

    public static function sexFor(string $firstName): Sex
    {
        $word = Str::of($firstName)->lower()->before(' ')->toString();

        if (in_array($word, self::FEMALE, true)) {
            return Sex::Female;
        }

        if (in_array($word, self::MALE, true)) {
            return Sex::Male;
        }

        return preg_match('/(a|e|y|ie)$/', $word) === 1 ? Sex::Female : Sex::Male;
    }

    /**
     * A hire date that fits the kind of person: deans are the longest serving, part-time faculty the most recent.
     *
     * @param  'dean'|'secretary'|'full_time'|'part_time'|'staff'  $kind
     */
    public static function hiredOn(string $kind): string
    {
        [$from, $to] = match ($kind) {
            'dean' => [2006, 2014],
            'secretary' => [2010, 2022],
            'part_time' => [2015, 2025],
            'staff' => [2012, 2025],
            default => [2009, 2024],
        };

        return fake()->dateTimeBetween("{$from}-01-01", "{$to}-12-31")->format('Y-m-d');
    }

    /**
     * The personal details of one employee, consistent with their name and hire date.
     *
     * @param  'dean'|'secretary'|'full_time'|'part_time'|'staff'  $kind
     * @return array<string, mixed>
     */
    public static function details(string $first, string $last, string $kind, string $hiredOn): array
    {
        $ageAtHire = $kind === 'dean' ? fake()->numberBetween(38, 48) : fake()->numberBetween(24, 45);

        return [
            'sex' => self::sexFor($first),
            'marital_status' => fake()->randomElement([MaritalStatus::Single, MaritalStatus::Married, MaritalStatus::Married, MaritalStatus::Married, MaritalStatus::Widowed, MaritalStatus::Separated]),
            'birthdate' => date('Y-m-d', strtotime($hiredOn.' -'.$ageAtHire.' years -'.fake()->numberBetween(0, 364).' days')),
            'phone' => '09'.fake()->numerify('#########'),
            'address' => fake()->numberBetween(10, 400).' '.fake()->randomElement(self::STREETS).', Pasig City',
            // example.test is reserved for examples, so a message to it can never reach a real person.
            'personal_email' => Str::of($first.'.'.$last)->ascii()->lower()->replaceMatches('/[^a-z.]/', '')->toString().fake()->numberBetween(10, 99).'@example.test',
        ];
    }

    /**
     * Made-up government ID numbers in the right formats. Part-time staff have no GSIS number.
     *
     * @return array<string, string>
     */
    public static function governmentIds(bool $fullTime): array
    {
        return [
            'tin' => fake()->numerify('###-###-###-000'),
            'sss' => fake()->numerify('##-#######-#'),
            'philhealth' => fake()->numerify('##-#########-#'),
            'pagibig' => fake()->numerify('####-####-####'),
            ...($fullTime ? ['gsis' => fake()->numerify('###########')] : []),
            ...(fake()->boolean(60) ? ['bir' => fake()->numerify('###-###-###')] : []),
        ];
    }

    /**
     * @return array{name: string, relationship: string, phone: string, address: string}
     */
    public static function emergencyContact(string $address): array
    {
        return [
            'name' => fake()->randomElement(self::FIRST_NAMES).' '.fake()->randomElement(self::LAST_NAMES),
            'relationship' => fake()->randomElement(['Spouse', 'Parent', 'Sibling', 'Child']),
            'phone' => '09'.fake()->numerify('#########'),
            'address' => $address,
        ];
    }
}
