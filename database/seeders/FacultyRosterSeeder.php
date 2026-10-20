<?php

namespace Database\Seeders;

use App\Enums\EmploymentType;
use App\Models\College;
use App\Models\Employee;
use App\Models\JobTitle;

/**
 * PLP's faculty and college secretaries, by name, as the owner supplied them. Only the name, college, and full-time or
 * part-time status are known. Everything else (job title rank, hire date, sex, civil status, contacts, and government
 * IDs) is left blank for HR to complete, except the job title, which is a placeholder rank because every employee
 * needs one. Where the owner has not supplied names (the part-time lists of CAS, CBA, and CON, and CON's faculty), the
 * list stays empty. Entries marked (Dean) become the college's dean; (College Secretary) entries get the College
 * Secretary title.
 */
class FacultyRosterSeeder
{
    /**
     * Raw names as supplied, by college code, then full-time (ft) and part-time (pt).
     *
     * @var array<string, array{ft: list<string>, pt: list<string>}>
     */
    private const ROSTER = [
        'COED' => [
            'ft' => [
                'Maila N. Unsay (Dean)', 'Cecile DM. Espiritu', 'Daniel N. Dapis', 'Dionimar B. Banaria', 'Anna Lee G. Baguio',
                'Rommel John L. Aballe', 'Andy C. Giova', 'Eva J. Ompoc', 'Magdalena O. Sorillano', 'Marlyn D. Tosolosa',
                'Maria Diana Carla R. Quiogue (College Secretary)',
            ],
            'pt' => [
                'Realiza Marimon', 'Marisol Fabie', 'Lorna Caballero', 'Michelle Siapco', 'Joel Zamora', 'Raymond Pereyra',
                'Rhea Iranzo', 'Charm Gladys Siriano', 'Glady Dela Cruz', 'Rosario Elena Falguera', 'Jennifer Hobrero',
                'Rochelle Laranang', 'Bernadette Lugtu', 'Sheryl Nazareno', 'Carmelita Peralta', 'Kristine Bernadette Tan',
            ],
        ],
        'COE' => [
            'ft' => [
                'Godofredo S. Zapanta (Dean)', 'Jasmin Caren S. Cruz (College Secretary)', 'Alberto A. Hobrero',
                'Darwin L. Francisco', 'Karen V. Arguelles', 'Sherlyn B. Avendano', 'Jonathan Diosano',
            ],
            'pt' => ['Michael S. Matias', 'Antonio L. Suinan', 'Romeo V. Bawa Jr', 'Eugene H. Embalzado Jr.', 'Vincent G. Ronquillo'],
        ],
        'CCS' => [
            'ft' => [
                'Riegie D. Tan (Dean DIT)', 'Rodolfo A. Mirabel Jr. (MT)', 'Rebecca R. Fajardo (DIT)', 'Federico G. Nueva (MT)',
                'Noreen A. Perez  (DIT)', 'Laura M. Althea (PhDTE)', 'Racquel A. Cortez (MIT, an Engr.)', 'Greta M. Rosario (DIT)',
                'Randy R. Otero (MIT)', 'Catherine B. Sorbito (MSIT)', 'Juanito P. Alvarez Jr. (DIT)', 'Ruby Jane Diosa (College Secretary)',
            ],
            'pt' => [
                'Janice Paola D. Congzon (MIT)', 'John Ronnie P. Chu', 'Jayson A. Daluyon (DIT)', 'Joseph Wilfred T. Dela Cruz',
                'Alexen A. Elacio (DIT)', 'Norman S. Espiritu (MSIT)', 'Michael M. Fernandez (MSIT)', 'Eliseo D. Francisco Jr. (MIT)',
                'Ramil N. Madriaga (MSIT)', 'Joel B. Mangaba (DT)', 'Gerald D.Paguio', 'Janeth M. Pineda (MIT)', 'Samantha D. Siao (MSIT)',
            ],
        ],
        'CAS' => [
            'ft' => [
                'Dr. Carolyn A. Alvero  (Dean)', 'Marilyn T. San Jose (College Secretary)', 'Raymundo J. Baui', 'Joseph S. Cruz',
                'Carmina M. Digala', 'Mark Anthony N. Lataza', 'Fidel D. Magday Jr', 'Benito T. Villaruel', 'Lea S. Velasco',
                'Marjorie P. Arce', 'Mark Christian M. Francisco', 'Elene R. Javite',
            ],
            'pt' => [],
        ],
        'CBA' => [
            'ft' => ['Rajesh Michael J.  Cordova (Dean', 'Kristine A. Nanez (College Secretary)', 'Jessica M. Avila', 'Marife P. Tapang'],
            'pt' => [],
        ],
        // CIHM: the owner's list also names Marilyn T. San Jose as part-time, but she is already the CAS College Secretary.
        // One person has one record here, so she is not added a second time. No dean was supplied.
        'CIHM' => [
            'ft' => [
                'EUREKA MAY A. CANON, MBA', 'ROWENA R. DE LEON, MBA', 'MARIA NERIZZA GINETH J. ERESO, MBA – HR',
                'BELLIE GRACE N. GOMEZ, MBA', 'ENRIQUE ANTONIO S. PEREZ, MBA-THM', 'DARWIN V. TANCHING, MBA',
                'KHRESSA JOY B. BENTIC, MBA', 'CHEF ENRIQUE LUCES',
            ],
            'pt' => [
                'DARYL ACE V. CORNELL', 'JOVID MARICAR D. MARANAN', 'CHEF JUNE H. PERALTA', 'CHEF RODERICK D.C. TUAZON',
                'JOJO M. VILLAMIN, MBA', 'ROWENA A. LUNAR', 'JEROME C. MAURICIO', 'CHEF VONJORDAN EMMANUEL A. PELISIGAS',
            ],
        ],
        // CON: only the dean is known so far.
        'CON' => [
            'ft' => ['Dr. Michelle E. Flores (Dean)'],
            'pt' => [],
        ],
    ];

    /**
     * Placeholder titles for full-time and part-time faculty, until HR records the real ones. The dean's title is Dean.
     */
    private const FULL_TIME_RANKS = ['Instructor I', 'Instructor II', 'Instructor III', 'Assistant Professor I', 'Assistant Professor II', 'Assistant Professor III', 'Associate Professor I'];

    private const PART_TIME_RANKS = ['Instructor I', 'Instructor II', 'Instructor III', 'Assistant Professor I'];

    private const NAME_PARTICLES = ['dela', 'de', 'del', 'delos', 'san', 'santa'];

    /**
     * Everyone on the list, one entry per person, ready for EmployeeSeeder to give a hire date, details, and a login.
     *
     * @return list<array{first: string, middle: ?string, last: string, suffix: ?string, college: College, office: null, jobTitle: JobTitle, type: EmploymentType, kind: string, isDean: bool}>
     */
    public static function specs(): array
    {
        $specs = [];

        foreach (self::ROSTER as $collegeCode => $groups) {
            $college = College::query()->where('code', $collegeCode)->firstOrFail();

            foreach ($groups as $group => $names) {
                $type = $group === 'ft' ? EmploymentType::FullTime : EmploymentType::PartTime;

                foreach ($names as $raw) {
                    $person = self::parseName($raw);
                    $titleName = match (true) {
                        $person['isSecretary'] => 'College Secretary',
                        $person['isDean'] => 'Dean',
                        default => fake()->randomElement($type === EmploymentType::FullTime ? self::FULL_TIME_RANKS : self::PART_TIME_RANKS),
                    };

                    $specs[] = [
                        'first' => $person['first'],
                        'middle' => $person['middle'],
                        'last' => $person['last'],
                        'suffix' => $person['suffix'],
                        'college' => $college,
                        'office' => null,
                        'jobTitle' => JobTitle::query()->where('college_id', $college->id)->where('title', $titleName)->firstOrFail(),
                        'type' => $type,
                        'kind' => match (true) {
                            $person['isDean'] => 'dean',
                            $person['isSecretary'] => 'secretary',
                            default => $type === EmploymentType::FullTime ? 'full_time' : 'part_time',
                        },
                        'isDean' => $person['isDean'],
                    ];
                }
            }
        }

        return $specs;
    }

    /**
     * Splits a name as written in the owner's list into its parts and notes. Text in parentheses is a role or degree
     * ("Dean", "College Secretary", "MIT"); a trailing "Jr" or "Sr" becomes the suffix; a single or double initial before
     * the surname is the middle name; "Dela" and similar particles stay with the surname.
     *
     * @return array{first: string, middle: ?string, last: string, suffix: ?string, isDean: bool, isSecretary: bool}
     */
    public static function parseName(string $raw): array
    {
        preg_match('/\(([^)]*)\)?/', $raw, $note);
        $note = mb_strtolower($note[1] ?? '');

        // Brackets and anything after a comma are degrees or notes (", MBA", ", MS-HRM"), not part of the name.
        $name = trim((string) preg_replace('/,.*$/', '', (string) preg_replace('/\(.*$/', '', $raw)));
        $name = trim((string) preg_replace('/\s+/', ' ', $name));

        // A name typed in capitals is turned into normal capitalization ("DE LEON" becomes "De Leon").
        if ($name === mb_strtoupper($name)) {
            $name = mb_convert_case($name, MB_CASE_TITLE);
            $name = (string) preg_replace_callback('/\.([a-z])/', fn (array $match): string => '.'.mb_strtoupper($match[1]), $name);
        }

        $name = (string) preg_replace('/\b([A-Z]{1,2})\.(?=[A-Z][a-z])/', '$1. ', $name);
        $name = (string) preg_replace('/^(Dr|Engr|Chef)\.?\s+/i', '', $name);
        $tokens = explode(' ', $name);

        $suffix = null;

        if (preg_match('/^(Jr|Sr)\.?$/i', end($tokens)) === 1) {
            $suffix = ucfirst(mb_strtolower(rtrim((string) array_pop($tokens), '.'))).'.';
        }

        $last = (string) array_pop($tokens);

        if ($tokens !== [] && in_array(mb_strtolower(end($tokens)), self::NAME_PARTICLES, true)) {
            $last = array_pop($tokens).' '.$last;
        }

        $middle = null;

        if ($tokens !== [] && preg_match('/^(?:[A-Z]{1,2}\.|(?:[A-Z]\.){2})$/', end($tokens)) === 1) {
            $middle = array_pop($tokens);
        }

        return [
            'first' => implode(' ', $tokens),
            'middle' => $middle,
            'last' => $last,
            'suffix' => $suffix,
            'isDean' => str_contains($note, 'dean'),
            'isSecretary' => str_contains($note, 'college secretary'),
        ];
    }
}
