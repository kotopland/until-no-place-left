<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Npc;
use App\Models\User;

class PersonAvatarService
{
    /**
     * Resolves the country flag emoji for a given nationality name.
     */
    public function flagEmoji(string $nationality): string
    {
        return match (strtolower(trim($nationality))) {
            'japanese', 'japan', 'jp', 'nihon' => '🇯🇵',
            'american', 'united states', 'usa', 'us' => '🇺🇸',
            'brazilian', 'brazil', 'br' => '🇧🇷',
            'korean', 'south korea', 'korea', 'kr' => '🇰🇷',
            'canadian', 'canada', 'ca' => '🇨🇦',
            'british', 'united kingdom', 'uk', 'gb', 'england', 'scotland', 'wales' => '🇬🇧',
            'australian', 'australia', 'au' => '🇦🇺',
            'filipino', 'philippines', 'ph' => '🇵🇭',
            'vietnamese', 'vietnam', 'vn' => '🇻🇳',
            'chinese', 'china', 'cn' => '🇨🇳',
            'taiwanese', 'taiwan', 'tw' => '🇹🇼',
            'singaporean', 'singapore', 'sg' => '🇸🇬',
            'indonesian', 'indonesia', 'id' => '🇮🇩',
            'german', 'germany', 'de' => '🇩🇪',
            'new zealander', 'new zealand', 'nz', 'kiwi' => '🇳🇿',
            'indian', 'india', 'in' => '🇮🇳',
            'mexican', 'mexico', 'mx' => '🇲🇽',
            'nigerian', 'nigeria', 'ng' => '🇳🇬',
            'south african', 'south africa', 'za' => '🇿🇦',
            'peruvian', 'peru', 'pe' => '🇵🇪',
            'argentinian', 'argentina', 'ar' => '🇦🇷',
            'armenian', 'armenia', 'am' => '🇦🇲',
            'austrian', 'austria', 'at' => '🇦🇹',
            'bangladeshi', 'bangladesh', 'bd' => '🇧🇩',
            'belgian', 'belgium', 'be' => '🇧🇪',
            'bolivian', 'bolivia', 'bo' => '🇧🇴',
            'cambodian', 'cambodia', 'kh' => '🇰🇭',
            'cameroonian', 'cameroon', 'cm' => '🇨🇲',
            'chilean', 'chile', 'cl' => '🇨🇱',
            'colombian', 'colombia', 'co' => '🇨🇴',
            'congolese', 'dr congo', 'congo', 'cd' => '🇨🇩',
            'costa rican', 'costa rica', 'cr' => '🇨🇷',
            'croatian', 'croatia', 'hr' => '🇭🇷',
            'cuban', 'cuba', 'cu' => '🇨🇺',
            'czech', 'czech republic', 'czechia', 'cz' => '🇨🇿',
            'danish', 'denmark', 'dk' => '🇩🇰',
            'dominican', 'dominican republic', 'do' => '🇩🇴',
            'dutch', 'netherlands', 'holland', 'nl' => '🇳🇱',
            'ecuadorian', 'ecuador', 'ec' => '🇪🇨',
            'egyptian', 'egypt', 'eg' => '🇪🇬',
            'emirati', 'united arab emirates', 'uae', 'ae' => '🇦🇪',
            'ethiopian', 'ethiopia', 'et' => '🇪🇹',
            'fijian', 'fiji', 'fj' => '🇫🇯',
            'finnish', 'finland', 'fi' => '🇫🇮',
            'french', 'france', 'fr' => '🇫🇷',
            'georgian', 'georgia', 'ge' => '🇬🇪',
            'ghanaian', 'ghana', 'gh' => '🇬🇭',
            'greek', 'greece', 'gr' => '🇬🇷',
            'guatemalan', 'guatemala', 'gt' => '🇬🇹',
            'honduran', 'honduras', 'hn' => '🇭🇳',
            'hong konger', 'hong kong', 'hk' => '🇭🇰',
            'hungarian', 'hungary', 'hu' => '🇭🇺',
            'icelandic', 'iceland', 'is' => '🇮🇸',
            'irish', 'ireland', 'ie' => '🇮🇪',
            'israeli', 'israel', 'il' => '🇮🇱',
            'italian', 'italy', 'it' => '🇮🇹',
            'ivorian', 'ivory coast', 'cote d\'ivoire', 'ci' => '🇨🇮',
            'jamaican', 'jamaica', 'jm' => '🇯🇲',
            'jordanian', 'jordan', 'jo' => '🇯🇴',
            'kenyan', 'kenya', 'ke' => '🇰🇪',
            'lebanese', 'lebanon', 'lb' => '🇱🇧',
            'madagascar', 'malagasy', 'mg' => '🇲🇬',
            'malaysian', 'malaysia', 'my' => '🇲🇾',
            'mongolian', 'mongolia', 'mn' => '🇲🇳',
            'moroccan', 'morocco', 'ma' => '🇲🇦',
            'burmese', 'myanmar', 'burma', 'mm' => '🇲🇲',
            'nepali', 'nepal', 'np' => '🇳🇵',
            'norwegian', 'norway', 'no' => '🇳🇴',
            'pakistani', 'pakistan', 'pk' => '🇵🇰',
            'panamanian', 'panama', 'pa' => '🇵🇦',
            'papua new guinean', 'papua new guinea', 'png', 'pg' => '🇵🇬',
            'paraguayan', 'paraguay', 'py' => '🇵🇾',
            'polish', 'poland', 'pl' => '🇵🇱',
            'portuguese', 'portugal', 'pt' => '🇵🇹',
            'puerto rican', 'puerto rico', 'pr' => '🇵🇷',
            'romanian', 'romania', 'ro' => '🇷🇴',
            'rwandan', 'rwanda', 'rw' => '🇷🇼',
            'salvadoran', 'el salvador', 'sv' => '🇸🇻',
            'samoan', 'samoa', 'ws' => '🇼🇸',
            'senegalese', 'senegal', 'sn' => '🇸🇳',
            'slovak', 'slovakia', 'sk' => '🇸🇰',
            'spanish', 'spain', 'es' => '🇪🇸',
            'sri lankan', 'sri lanka', 'lk' => '🇱🇰',
            'swedish', 'sweden', 'se' => '🇸🇪',
            'swiss', 'switzerland', 'ch' => '🇨🇭',
            'tanzanian', 'tanzania', 'tz' => '🇹🇿',
            'thai', 'thailand', 'th' => '🇹🇭',
            'tongan', 'tonga', 'to' => '🇹🇴',
            'turkish', 'turkey', 'tr' => '🇹🇷',
            'ugandan', 'uganda', 'ug' => '🇺🇬',
            'ukrainian', 'ukraine', 'ua' => '🇺🇦',
            'uruguayan', 'uruguay', 'uy' => '🇺🇾',
            'venezuelan', 'venezuela', 've' => '🇻🇪',
            'zambian', 'zambia', 'zm' => '🇿🇲',
            'zimbabwean', 'zimbabwe', 'zw' => '🇿🇼',
            default => '🌐',
        };
    }

    /**
     * Resolves the ISO 2-letter country code for a nationality.
     */
    public function countryCode(string $nationality): string
    {
        return match (strtolower(trim($nationality))) {
            'japanese', 'japan', 'jp', 'nihon' => 'JP',
            'american', 'united states', 'usa', 'us' => 'US',
            'brazilian', 'brazil', 'br' => 'BR',
            'korean', 'south korea', 'korea', 'kr' => 'KR',
            'canadian', 'canada', 'ca' => 'CA',
            'british', 'united kingdom', 'uk', 'gb', 'england', 'scotland', 'wales' => 'GB',
            'australian', 'australia', 'au' => 'AU',
            'filipino', 'philippines', 'ph' => 'PH',
            'vietnamese', 'vietnam', 'vn' => 'VN',
            'chinese', 'china', 'cn' => 'CN',
            'taiwanese', 'taiwan', 'tw' => 'TW',
            'singaporean', 'singapore', 'sg' => 'SG',
            'indonesian', 'indonesia', 'id' => 'ID',
            'german', 'germany', 'de' => 'DE',
            'new zealander', 'new zealand', 'nz', 'kiwi' => 'NZ',
            'indian', 'india', 'in' => 'IN',
            'mexican', 'mexico', 'mx' => 'MX',
            'nigerian', 'nigeria', 'ng' => 'NG',
            'south african', 'south africa', 'za' => 'ZA',
            'peruvian', 'peru', 'pe' => 'PE',
            'argentinian', 'argentina', 'ar' => 'AR',
            'armenian', 'armenia', 'am' => 'AM',
            'austrian', 'austria', 'at' => 'AT',
            'bangladeshi', 'bangladesh', 'bd' => 'BD',
            'belgian', 'belgium', 'be' => 'BE',
            'bolivian', 'bolivia', 'bo' => 'BO',
            'cambodian', 'cambodia', 'kh' => 'KH',
            'cameroonian', 'cameroon', 'cm' => 'CM',
            'chilean', 'chile', 'cl' => 'CL',
            'colombian', 'colombia', 'co' => 'CO',
            'congolese', 'dr congo', 'congo', 'cd' => 'CD',
            'costa rican', 'costa rica', 'cr' => 'CR',
            'croatian', 'croatia', 'hr' => 'HR',
            'cuban', 'cuba', 'cu' => 'CU',
            'czech', 'czech republic', 'czechia', 'cz' => 'CZ',
            'danish', 'denmark', 'dk' => 'DK',
            'dominican', 'dominican republic', 'do' => 'DO',
            'dutch', 'netherlands', 'holland', 'nl' => 'NL',
            'ecuadorian', 'ecuador', 'ec' => 'EC',
            'egyptian', 'egypt', 'eg' => 'EG',
            'emirati', 'united arab emirates', 'uae', 'ae' => 'AE',
            'ethiopian', 'ethiopia', 'et' => 'ET',
            'fijian', 'fiji', 'fj' => 'FJ',
            'finnish', 'finland', 'fi' => 'FI',
            'french', 'france', 'fr' => 'FR',
            'georgian', 'georgia', 'ge' => 'GE',
            'ghanaian', 'ghana', 'gh' => 'GH',
            'greek', 'greece', 'gr' => 'GR',
            'guatemalan', 'guatemala', 'gt' => 'GT',
            'honduran', 'honduras', 'hn' => 'HN',
            'hong konger', 'hong kong', 'hk' => 'HK',
            'hungarian', 'hungary', 'hu' => 'HU',
            'icelandic', 'iceland', 'is' => 'IS',
            'irish', 'ireland', 'ie' => 'IE',
            'israeli', 'israel', 'il' => 'IL',
            'italian', 'italy', 'it' => 'IT',
            'ivorian', 'ivory coast', 'cote d\'ivoire', 'ci' => 'CI',
            'jamaican', 'jamaica', 'jm' => 'JM',
            'jordanian', 'jordan', 'jo' => 'JO',
            'kenyan', 'kenya', 'ke' => 'KE',
            'lebanese', 'lebanon', 'lb' => 'LB',
            'madagascar', 'malagasy', 'mg' => 'MG',
            'malaysian', 'malaysia', 'my' => 'MY',
            'mongolian', 'mongolia', 'mn' => 'MN',
            'moroccan', 'morocco', 'ma' => 'MA',
            'burmese', 'myanmar', 'burma', 'mm' => 'MM',
            'nepali', 'nepal', 'np' => 'NP',
            'norwegian', 'norway', 'no' => 'NO',
            'pakistani', 'pakistan', 'pk' => 'PK',
            'panamanian', 'panama', 'pa' => 'PA',
            'papua new guinean', 'papua new guinea', 'png', 'pg' => 'PG',
            'paraguayan', 'paraguay', 'py' => 'PY',
            'polish', 'poland', 'pl' => 'PL',
            'portuguese', 'portugal', 'pt' => 'PT',
            'puerto rican', 'puerto rico', 'pr' => 'PR',
            'romanian', 'romania', 'ro' => 'RO',
            'rwandan', 'rwanda', 'rw' => 'RW',
            'salvadoran', 'el salvador', 'sv' => 'SV',
            'samoan', 'samoa', 'ws' => 'WS',
            'senegalese', 'senegal', 'sn' => 'SN',
            'slovak', 'slovakia', 'sk' => 'SK',
            'spanish', 'spain', 'es' => 'ES',
            'sri lankan', 'sri lanka', 'lk' => 'LK',
            'swedish', 'sweden', 'se' => 'SE',
            'swiss', 'switzerland', 'ch' => 'CH',
            'tanzanian', 'tanzania', 'tz' => 'TZ',
            'thai', 'thailand', 'th' => 'TH',
            'tongan', 'tonga', 'to' => 'TO',
            'turkish', 'turkey', 'tr' => 'TR',
            'ugandan', 'uganda', 'ug' => 'UG',
            'ukrainian', 'ukraine', 'ua' => 'UA',
            'uruguayan', 'uruguay', 'uy' => 'UY',
            'venezuelan', 'venezuela', 've' => 'VE',
            'zambian', 'zambia', 'zm' => 'ZM',
            'zimbabwean', 'zimbabwe', 'zw' => 'ZW',
            default => 'UN',
        };
    }

    /**
     * Common missionary sending countries with labels, flags, and nationality keys.
     *
     * @return array<int, array{nationality: string, country: string, flag: string}>
     */
    public function commonCountries(): array
    {
        return [
            ['nationality' => 'Japanese', 'country' => 'Japan', 'flag' => '🇯🇵'],
            ['nationality' => 'American', 'country' => 'United States', 'flag' => '🇺🇸'],
            ['nationality' => 'Brazilian', 'country' => 'Brazil', 'flag' => '🇧🇷'],
            ['nationality' => 'Korean', 'country' => 'South Korea', 'flag' => '🇰🇷'],
            ['nationality' => 'Canadian', 'country' => 'Canada', 'flag' => '🇨🇦'],
            ['nationality' => 'British', 'country' => 'United Kingdom', 'flag' => '🇬🇧'],
            ['nationality' => 'Australian', 'country' => 'Australia', 'flag' => '🇦🇺'],
            ['nationality' => 'Filipino', 'country' => 'Philippines', 'flag' => '🇵🇭'],
            ['nationality' => 'Vietnamese', 'country' => 'Vietnam', 'flag' => '🇻🇳'],
            ['nationality' => 'Chinese', 'country' => 'China', 'flag' => '🇨🇳'],
            ['nationality' => 'Taiwanese', 'country' => 'Taiwan', 'flag' => '🇹🇼'],
            ['nationality' => 'Singaporean', 'country' => 'Singapore', 'flag' => '🇸🇬'],
            ['nationality' => 'Indonesian', 'country' => 'Indonesia', 'flag' => '🇮🇩'],
            ['nationality' => 'German', 'country' => 'Germany', 'flag' => '🇩🇪'],
            ['nationality' => 'New Zealander', 'country' => 'New Zealand', 'flag' => '🇳🇿'],
            ['nationality' => 'Indian', 'country' => 'India', 'flag' => '🇮🇳'],
            ['nationality' => 'Mexican', 'country' => 'Mexico', 'flag' => '🇲🇽'],
            ['nationality' => 'Nigerian', 'country' => 'Nigeria', 'flag' => '🇳🇬'],
            ['nationality' => 'South African', 'country' => 'South Africa', 'flag' => '🇿🇦'],
            ['nationality' => 'Peruvian', 'country' => 'Peru', 'flag' => '🇵🇪'],
            ['nationality' => 'Argentinian', 'country' => 'Argentina', 'flag' => '🇦🇷'],
            ['nationality' => 'Armenian', 'country' => 'Armenia', 'flag' => '🇦🇲'],
            ['nationality' => 'Austrian', 'country' => 'Austria', 'flag' => '🇦🇹'],
            ['nationality' => 'Bangladeshi', 'country' => 'Bangladesh', 'flag' => '🇧🇩'],
            ['nationality' => 'Belgian', 'country' => 'Belgium', 'flag' => '🇧🇪'],
            ['nationality' => 'Bolivian', 'country' => 'Bolivia', 'flag' => '🇧🇴'],
            ['nationality' => 'Cambodian', 'country' => 'Cambodia', 'flag' => '🇰🇭'],
            ['nationality' => 'Cameroonian', 'country' => 'Cameroon', 'flag' => '🇨🇲'],
            ['nationality' => 'Chilean', 'country' => 'Chile', 'flag' => '🇨🇱'],
            ['nationality' => 'Colombian', 'country' => 'Colombia', 'flag' => '🇨🇴'],
            ['nationality' => 'Congolese', 'country' => 'DR Congo', 'flag' => '🇨🇩'],
            ['nationality' => 'Costa Rican', 'country' => 'Costa Rica', 'flag' => '🇨🇷'],
            ['nationality' => 'Croatian', 'country' => 'Croatia', 'flag' => '🇭🇷'],
            ['nationality' => 'Cuban', 'country' => 'Cuba', 'flag' => '🇨🇺'],
            ['nationality' => 'Czech', 'country' => 'Czech Republic', 'flag' => '🇨🇿'],
            ['nationality' => 'Danish', 'country' => 'Denmark', 'flag' => '🇩🇰'],
            ['nationality' => 'Dominican', 'country' => 'Dominican Republic', 'flag' => '🇩🇴'],
            ['nationality' => 'Dutch', 'country' => 'Netherlands', 'flag' => '🇳🇱'],
            ['nationality' => 'Ecuadorian', 'country' => 'Ecuador', 'flag' => '🇪🇨'],
            ['nationality' => 'Egyptian', 'country' => 'Egypt', 'flag' => '🇪🇬'],
            ['nationality' => 'Emirati', 'country' => 'United Arab Emirates', 'flag' => '🇦🇪'],
            ['nationality' => 'Ethiopian', 'country' => 'Ethiopia', 'flag' => '🇪🇹'],
            ['nationality' => 'Fijian', 'country' => 'Fiji', 'flag' => '🇫🇯'],
            ['nationality' => 'Finnish', 'country' => 'Finland', 'flag' => '🇫🇮'],
            ['nationality' => 'French', 'country' => 'France', 'flag' => '🇫🇷'],
            ['nationality' => 'Georgian', 'country' => 'Georgia', 'flag' => '🇬🇪'],
            ['nationality' => 'Ghanaian', 'country' => 'Ghana', 'flag' => '🇬🇭'],
            ['nationality' => 'Greek', 'country' => 'Greece', 'flag' => '🇬🇷'],
            ['nationality' => 'Guatemalan', 'country' => 'Guatemala', 'flag' => '🇬🇹'],
            ['nationality' => 'Honduran', 'country' => 'Honduras', 'flag' => '🇭🇳'],
            ['nationality' => 'Hong Konger', 'country' => 'Hong Kong', 'flag' => '🇭🇰'],
            ['nationality' => 'Hungarian', 'country' => 'Hungary', 'flag' => '🇭🇺'],
            ['nationality' => 'Icelandic', 'country' => 'Iceland', 'flag' => '🇮🇸'],
            ['nationality' => 'Irish', 'country' => 'Ireland', 'flag' => '🇮🇪'],
            ['nationality' => 'Israeli', 'country' => 'Israel', 'flag' => '🇮🇱'],
            ['nationality' => 'Italian', 'country' => 'Italy', 'flag' => '🇮🇹'],
            ['nationality' => 'Ivorian', 'country' => 'Ivory Coast', 'flag' => '🇨🇮'],
            ['nationality' => 'Jamaican', 'country' => 'Jamaica', 'flag' => '🇯🇲'],
            ['nationality' => 'Jordanian', 'country' => 'Jordan', 'flag' => '🇯🇴'],
            ['nationality' => 'Kenyan', 'country' => 'Kenya', 'flag' => '🇰🇪'],
            ['nationality' => 'Lebanese', 'country' => 'Lebanon', 'flag' => '🇱🇧'],
            ['nationality' => 'Madagascar', 'country' => 'Madagascar', 'flag' => '🇲🇬'],
            ['nationality' => 'Malaysian', 'country' => 'Malaysia', 'flag' => '🇲🇾'],
            ['nationality' => 'Mongolian', 'country' => 'Mongolia', 'flag' => '🇲🇳'],
            ['nationality' => 'Moroccan', 'country' => 'Morocco', 'flag' => '🇲🇦'],
            ['nationality' => 'Burmese', 'country' => 'Myanmar', 'flag' => '🇲🇲'],
            ['nationality' => 'Nepali', 'country' => 'Nepal', 'flag' => '🇳🇵'],
            ['nationality' => 'Norwegian', 'country' => 'Norway', 'flag' => '🇳🇴'],
            ['nationality' => 'Pakistani', 'country' => 'Pakistan', 'flag' => '🇵🇰'],
            ['nationality' => 'Panamanian', 'country' => 'Panama', 'flag' => '🇵🇦'],
            ['nationality' => 'Papua New Guinean', 'country' => 'Papua New Guinea', 'flag' => '🇵🇬'],
            ['nationality' => 'Paraguayan', 'country' => 'Paraguay', 'flag' => '🇵🇾'],
            ['nationality' => 'Polish', 'country' => 'Poland', 'flag' => '🇵🇱'],
            ['nationality' => 'Portuguese', 'country' => 'Portugal', 'flag' => '🇵🇹'],
            ['nationality' => 'Puerto Rican', 'country' => 'Puerto Rico', 'flag' => '🇵🇷'],
            ['nationality' => 'Romanian', 'country' => 'Romania', 'flag' => '🇷🇴'],
            ['nationality' => 'Rwandan', 'country' => 'Rwanda', 'flag' => '🇷🇼'],
            ['nationality' => 'Salvadoran', 'country' => 'El Salvador', 'flag' => '🇸🇻'],
            ['nationality' => 'Samoan', 'country' => 'Samoa', 'flag' => '🇼🇸'],
            ['nationality' => 'Senegalese', 'country' => 'Senegal', 'flag' => '🇸🇳'],
            ['nationality' => 'Slovak', 'country' => 'Slovakia', 'flag' => '🇸🇰'],
            ['nationality' => 'Spanish', 'country' => 'Spain', 'flag' => '🇪🇸'],
            ['nationality' => 'Sri Lankan', 'country' => 'Sri Lanka', 'flag' => '🇱🇰'],
            ['nationality' => 'Swedish', 'country' => 'Sweden', 'flag' => '🇸🇪'],
            ['nationality' => 'Swiss', 'country' => 'Switzerland', 'flag' => '🇨🇭'],
            ['nationality' => 'Tanzanian', 'country' => 'Tanzania', 'flag' => '🇹🇿'],
            ['nationality' => 'Thai', 'country' => 'Thailand', 'flag' => '🇹🇭'],
            ['nationality' => 'Tongan', 'country' => 'Tonga', 'flag' => '🇹🇴'],
            ['nationality' => 'Turkish', 'country' => 'Turkey', 'flag' => '🇹🇷'],
            ['nationality' => 'Ugandan', 'country' => 'Uganda', 'flag' => '🇺🇬'],
            ['nationality' => 'Ukrainian', 'country' => 'Ukraine', 'flag' => '🇺🇦'],
            ['nationality' => 'Emirati', 'country' => 'United Arab Emirates', 'flag' => '🇦🇪'],
            ['nationality' => 'Uruguayan', 'country' => 'Uruguay', 'flag' => '🇺🇾'],
            ['nationality' => 'Venezuelan', 'country' => 'Venezuela', 'flag' => '🇻🇪'],
            ['nationality' => 'Zambian', 'country' => 'Zambia', 'flag' => '🇿🇲'],
            ['nationality' => 'Zimbabwean', 'country' => 'Zimbabwe', 'flag' => '🇿🇼'],
        ];
    }

    /**
     * Resolves gender icon (♂ or ♀).
     */
    public function genderIcon(string $gender): string
    {
        return match (strtolower(trim($gender))) {
            'female', 'f', 'woman', 'girl' => '♀',
            default => '♂',
        };
    }

    /**
     * Resolves age bracket key.
     */
    public function ageGroup(int $age): string
    {
        return match (true) {
            $age < 25 => 'youth',
            $age < 50 => 'adult',
            $age < 65 => 'mature',
            default => 'senior',
        };
    }

    /**
     * Resolves human-readable age group label.
     */
    public function ageGroupLabel(int $age): string
    {
        return match (true) {
            $age < 25 => 'Youth (18-24)',
            $age < 50 => 'Adult (25-49)',
            $age < 65 => 'Mature (50-64)',
            default => 'Senior (65+)',
        };
    }

    /**
     * Generates a richly styled SVG avatar representing nationality, gender, and age.
     */
    public function generateSvg(
        string $gender = 'male',
        int $age = 30,
        string $nationality = 'Japanese',
        ?string $role = null,
        ?string $status = null,
        int $pixelSize = 64,
        bool $includeBadges = false,
        ?string $seed = null
    ): string {
        $isFemale = in_array(strtolower(trim($gender)), ['female', 'f', 'woman', 'girl'], true);
        $ageGroup = $this->ageGroup($age);
        $flag = $this->flagEmoji($nationality);
        $genderSymbol = $this->genderIcon($gender);
        $natLower = strtolower(trim($nationality));

        // Skin tones tailored by nationality & demographic
        $skinBase = match ($natLower) {
            'vietnamese', 'vietnam', 'vn', 'thai', 'thailand', 'th', 'burmese', 'myanmar', 'cambodian', 'cambodia', 'lao', 'laos' => '#fde047', // Warm golden
            'brazilian', 'brazil', 'br', 'filipino', 'philippines', 'ph', 'indonesian', 'indonesia', 'id', 'malaysian', 'malaysia', 'my', 'samoan', 'tongan', 'fijian', 'papua new guinean' => '#f59e0b', // Warm honey caramel
            'american', 'united states', 'usa', 'us', 'canadian', 'canada', 'ca', 'british', 'uk', 'australian', 'german', 'dutch', 'french', 'irish', 'swedish', 'norwegian', 'danish', 'finnish', 'polish', 'ukrainian', 'swiss', 'austrian', 'new zealander', 'czech', 'hungarian', 'slovak', 'croatian', 'icelandic', 'belgian' => '#fed7aa', // Light peach
            'nigerian', 'nigeria', 'ghanaian', 'ghana', 'kenyan', 'kenya', 'ethiopian', 'ethiopia', 'south african', 'congolese', 'ugandan', 'tanzanian', 'cameroonian', 'zambian', 'zimbabwean', 'rwandan', 'ivorian', 'senegalese', 'jamaican' => '#78350f', // Rich deep brown
            'indian', 'india', 'pakistani', 'pakistan', 'bangladeshi', 'bangladesh', 'sri lankan', 'nepali' => '#b45309', // Warm deep amber-brown
            'mexican', 'mexico', 'colombian', 'colombia', 'peruvian', 'peru', 'ecuadorian', 'chilean', 'guatemalan', 'bolivian', 'argentinian', 'costa rican', 'salvadoran', 'honduran', 'panamanian', 'paraguayan', 'uruguayan', 'venezuelan', 'cuban', 'dominican', 'puerto rican', 'egyptian', 'moroccan' => '#d97706', // Warm golden-tan
            'chinese', 'china', 'cn', 'taiwanese', 'taiwan', 'tw', 'hong konger', 'hong kong', 'singaporean', 'singapore' => '#fef08a', // Fair warm
            'korean', 'south korea', 'kr' => '#fef9c3', // Pale ivory
            default => '#fde68a', // Japanese soft warm tone
        };
        $skinShadow = match ($natLower) {
            'nigerian', 'nigeria', 'ghanaian', 'ghana', 'kenyan', 'kenya', 'ethiopian', 'ethiopia', 'south african', 'congolese', 'ugandan', 'tanzanian', 'cameroonian', 'zambian', 'zimbabwean', 'rwandan', 'ivorian', 'senegalese', 'jamaican' => '#451a03',
            'indian', 'india', 'pakistani', 'bangladeshi', 'sri lankan', 'nepali' => '#78350f',
            'brazilian', 'brazil', 'br', 'filipino', 'philippines', 'mexican', 'mexico', 'colombian', 'peruvian' => '#b45309',
            'american', 'usa', 'canadian', 'british', 'german', 'dutch', 'french', 'australian' => '#fdba74',
            default => '#fcd34d',
        };

        // Hair colors based on age & nationality
        $hairColor = match ($ageGroup) {
            'senior' => '#e2e8f0', // Silver / white
            'mature' => '#64748b', // Salt and pepper slate
            default => match ($natLower) {
                'american', 'usa', 'canadian', 'british', 'german', 'australian', 'dutch', 'swedish', 'norwegian', 'danish', 'irish', 'new zealander' => '#b45309', // Chestnut / light brown
                default => '#1e1b4b', // Deep midnight black/espresso
            },
        };
        $hairHighlight = match ($ageGroup) {
            'senior' => '#ffffff',
            'mature' => '#94a3b8',
            default => match ($natLower) {
                'american', 'usa', 'canadian', 'british', 'german', 'australian', 'dutch', 'swedish', 'norwegian', 'danish', 'irish', 'new zealander' => '#d97706',
                default => '#312e81',
            },
        };

        // Background theme gradient based on status / role
        [$bgGradStart, $bgGradEnd, $ringColor] = match (strtolower((string) $status)) {
            'green', 'saved', 'baptized' => ['#064e3b', '#022c22', '#10b981'],
            'yellow' => ['#78350f', '#451a03', '#f59e0b'],
            'red' => ['#4c0519', '#1e1b4b', '#f43f5e'],
            default => ['#1e1b4b', '#0f172a', '#6366f1'],
        };

        // Clothing colors based on role & gender
        [$clothMain, $clothCollar] = match (strtolower((string) $role)) {
            'salaryman' => ['#1e293b', '#ffffff'], // Charcoal suit & crisp shirt
            'shopkeeper' => ['#0f766e', '#fef3c7'], // Teal shopkeeper apron
            'elderlyresident', 'elderly' => ['#475569', '#cbd5e1'], // Comfortable slate cardigan
            'universitystudent', 'student' => ['#4338ca', '#e0e7ff'], // Indigo student hoodie
            'internationalexpat', 'expat' => ['#0369a1', '#e0f2fe'], // Sky blue casual jacket
            'singleparent' => ['#be185d', '#fce7f3'], // Berry rose cardigan
            'homelessindividual' => ['#52525b', '#71717a'], // Layered neutral coat
            default => $isFemale ? ['#7c3aed', '#ede9fe'] : ['#2563eb', '#dbeafe'],
        };

        $idPrefix = 'av_'.substr(md5(($seed ?? $nationality).$gender.$age.$role), 0, 8);

        // SVG building blocks
        $svg = [];
        $svg[] = "<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\" width=\"{$pixelSize}\" height=\"{$pixelSize}\" class=\"inline-block select-none overflow-visible\">";

        // Defs (gradients and filters)
        $svg[] = '  <defs>';
        $svg[] = "    <radialGradient id=\"{$idPrefix}_bg\" cx=\"50%\" cy=\"35%\" r=\"65%\">";
        $svg[] = "      <stop offset=\"0%\" stop-color=\"{$bgGradStart}\" />";
        $svg[] = "      <stop offset=\"100%\" stop-color=\"{$bgGradEnd}\" />";
        $svg[] = '    </radialGradient>';
        $svg[] = "    <linearGradient id=\"{$idPrefix}_skin\" x1=\"0\" y1=\"0\" x2=\"0\" y2=\"1\">";
        $svg[] = "      <stop offset=\"0%\" stop-color=\"{$skinBase}\" />";
        $svg[] = "      <stop offset=\"100%\" stop-color=\"{$skinShadow}\" />";
        $svg[] = '    </linearGradient>';
        $svg[] = "    <linearGradient id=\"{$idPrefix}_hair\" x1=\"0\" y1=\"0\" x2=\"1\" y2=\"1\">";
        $svg[] = "      <stop offset=\"0%\" stop-color=\"{$hairHighlight}\" />";
        $svg[] = "      <stop offset=\"100%\" stop-color=\"{$hairColor}\" />";
        $svg[] = '    </linearGradient>';
        $svg[] = "    <filter id=\"{$idPrefix}_shadow\" x=\"-20%\" y=\"-20%\" width=\"140%\" height=\"140%\">";
        $svg[] = '      <feDropShadow dx=\"0\" dy=\"2\" stdDeviation=\"2\" flood-color=\"#000000\" flood-opacity=\"0.4\" />';
        $svg[] = '    </filter>';
        $svg[] = '  </defs>';

        // Background Avatar Canvas with Ring
        $svg[] = "  <rect x=\"2\" y=\"2\" width=\"96\" height=\"96\" rx=\"24\" fill=\"url(#{$idPrefix}_bg)\" stroke=\"{$ringColor}\" stroke-width=\"2\" />";

        // Back Hair (for females with longer hair, ponytail, or classic bun)
        if ($isFemale) {
            if ($ageGroup === 'youth') {
                // High bouncy ponytail and cascading hair
                $svg[] = "  <path d=\"M20 46 C16 66 20 86 30 92 L70 92 C80 86 84 66 80 46 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M68 28 C85 20 88 45 74 52 C70 42 66 32 68 28 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = '  <circle cx="67" cy="30" r="3.5" fill="#f43f5e" />'; // Hair ribbon
            } elseif ($ageGroup === 'senior') {
                // Classic silver bun with decorative hairpin
                $svg[] = "  <path d=\"M22 45 C18 64 22 82 30 88 L70 88 C78 82 82 64 78 45 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <circle cx=\"50\" cy=\"18\" r=\"12\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = '  <path d="M43 18 Q50 13 57 18" stroke="#cbd5e1" stroke-width="1.5" fill="none" />';
                $svg[] = '  <path d="M40 22 L60 14" stroke="#d97706" stroke-width="1.8" stroke-linecap="round" />';
            } else {
                // Rich flowing hair behind shoulders
                $svg[] = "  <path d=\"M18 42 C14 62 18 84 28 92 L72 92 C82 84 86 62 82 42 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
            }
        }

        // Torso / Shoulders & Attire
        if ($isFemale) {
            // Elegant feminine shoulder taper
            $svg[] = "  <path d=\"M20 98 C20 80 32 75 50 75 C68 75 80 80 80 98 Z\" fill=\"{$clothMain}\" filter=\"url(#{$idPrefix}_shadow)\" />";
        } else {
            // Structured masculine shoulders
            $svg[] = "  <path d=\"M16 98 C16 78 30 74 50 74 C70 74 84 78 84 98 Z\" fill=\"{$clothMain}\" filter=\"url(#{$idPrefix}_shadow)\" />";
        }

        // Collar & Accessories
        if (strtolower((string) $role) === 'salaryman') {
            if ($isFemale) {
                // Elegant professional office blouse with pearl necklace
                $svg[] = '  <!-- Pearl necklace and professional blouse -->';
                $svg[] = '  <path d="M41 75 L50 87 L59 75 Z" fill="#fdf2f8" />';
                $svg[] = "  <path d=\"M44 75 L50 82 L56 75 Z\" fill=\"{$clothCollar}\" />";
                $svg[] = '  <path d="M43 78 Q50 85 57 78" stroke="#e2e8f0" stroke-width="1.2" fill="none" />';
                $svg[] = '  <circle cx="50" cy="82" r="2.2" fill="#ffffff" stroke="#cbd5e1" stroke-width="0.6" />';
            } else {
                // White V-neck collar + red corporate tie
                $svg[] = "  <path d=\"M42 74 L50 86 L58 74 Z\" fill=\"{$clothCollar}\" />";
                $svg[] = '  <path d="M48 84 L52 84 L53 98 L47 98 Z" fill="#dc2626" />';
            }
        } elseif (strtolower((string) $role) === 'shopkeeper') {
            // Apron straps
            $svg[] = '  <path d="M34 74 L42 98 L46 98 L38 74 Z" fill="#115e59" />';
            $svg[] = '  <path d="M66 74 L58 98 L54 98 L62 74 Z" fill="#115e59" />';
            if ($isFemale) {
                $svg[] = '  <circle cx="50" cy="80" r="1.8" fill="#fef08a" />';
            }
        } elseif ($ageGroup === 'senior') {
            // Warm cardigan buttoned collar
            $svg[] = "  <path d=\"M44 74 L50 84 L56 74 Z\" fill=\"{$clothCollar}\" />";
            $svg[] = '  <circle cx="50" cy="89" r="1.5" fill="#94a3b8" />';
            $svg[] = '  <circle cx="50" cy="95" r="1.5" fill="#94a3b8" />';
        } else {
            // Casual neckline
            if ($isFemale) {
                $svg[] = "  <path d=\"M42 74 Q50 83 58 74 Z\" fill=\"{$clothCollar}\" />";
                $svg[] = '  <circle cx="50" cy="80" r="1.8" fill="#f43f5e" opacity="0.8" />';
            } else {
                $svg[] = "  <path d=\"M43 74 Q50 82 57 74 Z\" fill=\"{$clothCollar}\" />";
            }
        }

        // Neck
        $neckWidth = $isFemale ? 10 : 12;
        $neckX = $isFemale ? 45 : 44;
        $svg[] = "  <rect x=\"{$neckX}\" y=\"58\" width=\"{$neckWidth}\" height=\"18\" rx=\"3\" fill=\"{$skinShadow}\" />";

        // Head Shape
        if ($isFemale) {
            // Soft delicate feminine jawline & rounded chin
            $svg[] = "  <path d=\"M31 38 C31 23 69 23 69 38 C69 56 58 66 50 66 C42 66 31 56 31 38 Z\" fill=\"url(#{$idPrefix}_skin)\" filter=\"url(#{$idPrefix}_shadow)\" />";
        } else {
            // Structured masculine chin
            $svg[] = "  <path d=\"M29 40 C29 24 71 24 71 40 C71 60 59 70 50 70 C41 70 29 60 29 40 Z\" fill=\"url(#{$idPrefix}_skin)\" filter=\"url(#{$idPrefix}_shadow)\" />";
        }

        // Ears
        $svg[] = "  <circle cx=\"28\" cy=\"46\" r=\"4.5\" fill=\"url(#{$idPrefix}_skin)\" />";
        $svg[] = "  <circle cx=\"72\" cy=\"46\" r=\"4.5\" fill=\"url(#{$idPrefix}_skin)\" />";

        // Pretty earrings for female avatars
        if ($isFemale) {
            $svg[] = '  <circle cx="28.5" cy="49.5" r="1.8" fill="#ffffff" stroke="#cbd5e1" stroke-width="0.5" />';
            $svg[] = '  <circle cx="71.5" cy="49.5" r="1.8" fill="#ffffff" stroke="#cbd5e1" stroke-width="0.5" />';
        }

        // Cheeks / Blush
        if ($isFemale) {
            $svg[] = '  <ellipse cx="36" cy="51" rx="4.5" ry="3" fill="#fb7185" opacity="0.30" />';
            $svg[] = '  <ellipse cx="64" cy="51" rx="4.5" ry="3" fill="#fb7185" opacity="0.30" />';
        } elseif ($ageGroup === 'youth') {
            $svg[] = '  <circle cx="37" cy="52" r="3.5" fill="#f43f5e" opacity="0.20" />';
            $svg[] = '  <circle cx="63" cy="52" r="3.5" fill="#f43f5e" opacity="0.20" />';
        }

        // Senior Expression Lines
        if ($ageGroup === 'senior') {
            $svg[] = "  <path d=\"M33 46 Q30 47 33 49\" stroke=\"{$skinShadow}\" stroke-width=\"1.2\" fill=\"none\" stroke-linecap=\"round\" />";
            $svg[] = "  <path d=\"M67 46 Q70 47 67 49\" stroke=\"{$skinShadow}\" stroke-width=\"1.2\" fill=\"none\" stroke-linecap=\"round\" />";
            $svg[] = "  <path d=\"M41 57 Q43 61 46 62\" stroke=\"{$skinShadow}\" stroke-width=\"1.2\" fill=\"none\" stroke-linecap=\"round\" />";
            $svg[] = "  <path d=\"M59 57 Q57 61 54 62\" stroke=\"{$skinShadow}\" stroke-width=\"1.2\" fill=\"none\" stroke-linecap=\"round\" />";
        } elseif ($ageGroup === 'mature') {
            $svg[] = "  <path d=\"M43 59 Q45 62 47 62\" stroke=\"{$skinShadow}\" stroke-width=\"1\" fill=\"none\" stroke-linecap=\"round\" />";
            $svg[] = "  <path d=\"M57 59 Q55 62 53 62\" stroke=\"{$skinShadow}\" stroke-width=\"1\" fill=\"none\" stroke-linecap=\"round\" />";
        }

        // Eyes
        $eyeY = $isFemale ? 44.5 : 46;
        if ($isFemale) {
            // Expressive bright eyes with dual specular catchlights
            $svg[] = "  <ellipse cx=\"39.5\" cy=\"{$eyeY}\" rx=\"3.4\" ry=\"3.8\" fill=\"#0f172a\" />";
            $svg[] = '  <circle cx="41" cy="'.($eyeY - 1.2).'" r="1.3" fill="#ffffff" />';
            $svg[] = '  <circle cx="38.2" cy="'.($eyeY + 1.2).'" r="0.7" fill="#ffffff" opacity="0.8" />';

            $svg[] = "  <ellipse cx=\"60.5\" cy=\"{$eyeY}\" rx=\"3.4\" ry=\"3.8\" fill=\"#0f172a\" />";
            $svg[] = '  <circle cx="62" cy="'.($eyeY - 1.2).'" r="1.3" fill="#ffffff" />';
            $svg[] = '  <circle cx="59.2" cy="'.($eyeY + 1.2).'" r="0.7" fill="#ffffff" opacity="0.8" />';

            // Winged feminine eyelashes
            $svg[] = '  <path d="M34.5 '.($eyeY - 2.5).' Q39.5 '.($eyeY - 5.5).' 44.5 '.($eyeY - 2.5).'" stroke="#0f172a" stroke-width="1.8" fill="none" stroke-linecap="round" />';
            $svg[] = '  <path d="M35 '.($eyeY - 2.5).' L32.5 '.($eyeY - 4.2).'" stroke="#0f172a" stroke-width="1.5" stroke-linecap="round" />';

            $svg[] = '  <path d="M55.5 '.($eyeY - 2.5).' Q60.5 '.($eyeY - 5.5).' 65.5 '.($eyeY - 2.5).'" stroke="#0f172a" stroke-width="1.8" fill="none" stroke-linecap="round" />';
            $svg[] = '  <path d="M65 '.($eyeY - 2.5).' L67.5 '.($eyeY - 4.2).'" stroke="#0f172a" stroke-width="1.5" stroke-linecap="round" />';
        } else {
            $svg[] = "  <ellipse cx=\"40\" cy=\"{$eyeY}\" rx=\"3.2\" ry=\"3.5\" fill=\"#0f172a\" />";
            $svg[] = '  <circle cx="41.2" cy="'.($eyeY - 1.2).'" r="1.2" fill="#ffffff" />';
            $svg[] = "  <ellipse cx=\"60\" cy=\"{$eyeY}\" rx=\"3.2\" ry=\"3.5\" fill=\"#0f172a\" />";
            $svg[] = '  <circle cx="61.2" cy="'.($eyeY - 1.2).'" r="1.2" fill="#ffffff" />';
        }

        // Eyebrows
        $browColor = ($ageGroup === 'senior') ? '#94a3b8' : (($ageGroup === 'mature') ? '#475569' : '#1e1b4b');
        $browWidth = $isFemale ? '1.4' : '2.4';
        $browY = 39;
        $svg[] = "  <path d=\"M36 {$browY} Q41 ".($browY - 2)." 45 {$browY}\" stroke=\"{$browColor}\" stroke-width=\"{$browWidth}\" fill=\"none\" stroke-linecap=\"round\" />";
        $svg[] = "  <path d=\"M64 {$browY} Q59 ".($browY - 2)." 55 {$browY}\" stroke=\"{$browColor}\" stroke-width=\"{$browWidth}\" fill=\"none\" stroke-linecap=\"round\" />";

        // Nose
        $noseWidth = $isFemale ? '1.1' : '1.4';
        $svg[] = "  <path d=\"M50 46 L48.8 51.5 L51.2 51.5\" stroke=\"{$skinShadow}\" stroke-width=\"{$noseWidth}\" fill=\"none\" stroke-linecap=\"round\" stroke-linejoin=\"round\" />";

        // Mouth (Pleasant warm smile)
        if ($isFemale) {
            // Elegant contoured lips with soft rose/coral gloss
            $svg[] = '  <path d="M44 57.5 Q50 60 56 57.5 Q50 63.5 44 57.5 Z" fill="#fb7185" stroke="#e11d48" stroke-width="0.8" stroke-linejoin="round" />';
            $svg[] = '  <path d="M47 59.2 Q50 61.2 53 59.2" stroke="#ffffff" stroke-width="0.8" fill="none" stroke-linecap="round" opacity="0.6" />';
        } else {
            $svg[] = '  <path d="M44 58 Q50 63 56 58" stroke="#991b1b" stroke-width="1.8" fill="none" stroke-linecap="round" />';
        }

        // Glasses for Senior or male IT/salaryman
        $hasGlasses = ($ageGroup === 'senior') || (! $isFemale && strtolower((string) $role) === 'salaryman' && $age > 33);
        if ($hasGlasses) {
            $glassesFrame = ($ageGroup === 'senior') ? '#d97706' : '#94a3b8'; // Gold or silver frame
            $svg[] = "  <circle cx=\"40\" cy=\"{$eyeY}\" r=\"6.5\" fill=\"none\" stroke=\"{$glassesFrame}\" stroke-width=\"1.4\" />";
            $svg[] = "  <circle cx=\"60\" cy=\"{$eyeY}\" r=\"6.5\" fill=\"none\" stroke=\"{$glassesFrame}\" stroke-width=\"1.4\" />";
            $svg[] = "  <line x1=\"46.5\" y1=\"{$eyeY}\" x2=\"53.5\" y2=\"{$eyeY}\" stroke=\"{$glassesFrame}\" stroke-width=\"1.4\" />";
            $svg[] = "  <path d=\"M33.5 {$eyeY} L28 ".($eyeY - 1)."\" stroke=\"{$glassesFrame}\" stroke-width=\"1.2\" />";
            $svg[] = "  <path d=\"M66.5 {$eyeY} L72 ".($eyeY - 1)."\" stroke=\"{$glassesFrame}\" stroke-width=\"1.2\" />";
            // Subtle glass shine
            $svg[] = '  <path d="M38 '.($eyeY - 3).' L43 '.($eyeY + 2).'" stroke="#ffffff" stroke-width="1" stroke-linecap="round" opacity="0.6" />';
            $svg[] = '  <path d="M58 '.($eyeY - 3).' L63 '.($eyeY + 2).'" stroke="#ffffff" stroke-width="1" stroke-linecap="round" opacity="0.6" />';
        }

        // Front Hair
        if ($isFemale) {
            if ($ageGroup === 'senior') {
                // Soft wavy silver fringe & side curls
                $svg[] = "  <path d=\"M28 38 C34 22 66 22 72 38 C65 29 57 26 50 30 C43 26 35 29 28 38 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M26 38 C24 46 25 54 28 58 C30 52 29 44 29 38 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M74 38 C76 46 75 54 72 58 C70 52 71 44 71 38 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
            } elseif ($ageGroup === 'youth') {
                // Trendy modern bangs & long face-framing side locks
                $svg[] = "  <path d=\"M27 42 C25 28 35 21 50 21 C65 21 75 28 73 42 C69 33 62 29 54 31 C48 29 42 31 36 33 C32 35 29 39 27 42 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M26 38 C23 48 24 62 28 68 C31 62 30 48 30 38 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M74 38 C77 48 76 62 72 68 C69 62 70 48 70 38 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
            } else {
                // Shoulder-length stylish layered hair sweeping around face
                $svg[] = "  <path d=\"M27 40 C25 26 35 20 50 20 C65 20 75 26 73 40 C68 30 59 26 49 27 C39 26 31 30 27 40 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M25 36 C22 46 22 62 28 72 C31 65 30 48 29 36 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M75 36 C78 46 78 62 72 72 C69 65 70 48 71 36 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M38 23 Q49 21 62 25\" stroke=\"{$hairHighlight}\" stroke-width=\"1.4\" fill=\"none\" opacity=\"0.6\" />";
            }
        } else {
            // Male hair
            if ($ageGroup === 'senior') {
                // Distinguished silver thinning side hair
                $svg[] = "  <path d=\"M28 40 C28 30 33 28 36 32 C34 36 33 42 34 46 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M72 40 C72 30 67 28 64 32 C66 36 67 42 66 46 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M36 26 C42 24 58 24 64 26 C60 25 50 25 40 26 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
            } elseif ($ageGroup === 'youth') {
                // Modern textured/spiky student hair
                $svg[] = "  <path d=\"M27 40 C26 28 34 20 50 20 C66 20 74 28 73 40 C70 30 64 25 56 26 C53 23 48 22 44 26 C38 24 32 28 27 40 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M46 17 L52 23 L44 24 Z\" fill=\"url(#{$idPrefix}_hair)\" />"; // Dynamic spike
                $svg[] = "  <path d=\"M54 18 L58 24 L52 25 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
            } else {
                // Crisp side-parted salaryman/professional hair
                $svg[] = "  <path d=\"M27 40 C26 26 36 21 50 21 C65 21 74 26 73 40 C69 30 61 26 50 27 C42 25 34 28 27 40 Z\" fill=\"url(#{$idPrefix}_hair)\" />";
                $svg[] = "  <path d=\"M35 24 Q48 22 62 26\" stroke=\"{$hairHighlight}\" stroke-width=\"1.5\" fill=\"none\" />";
            }
        }

        // Integrated Badges (if includeBadges is true)
        if ($includeBadges) {
            // Flag Badge Top-Right
            $svg[] = '  <g transform="translate(70, 6)">';
            $svg[] = '    <circle cx=\"12\" cy=\"12\" r=\"11\" fill=\"#0f172a\" stroke=\"#334155\" stroke-width=\"1.5\" />';
            $svg[] = "    <text x=\"12\" y=\"16\" font-size=\"13\" text-anchor=\"middle\">{$flag}</text>";
            $svg[] = '  </g>';

            // Gender & Age Pill Bottom-Center
            $svg[] = '  <g transform="translate(18, 79)">';
            $svg[] = '    <rect x=\"0\" y=\"0\" width=\"64\" height=\"17\" rx=\"8.5\" fill=\"#0f172a\" fill-opacity=\"0.95\" stroke=\"#334155\" stroke-width=\"1.2\" />';
            $badgeColor = $isFemale ? '#f472b6' : '#60a5fa';
            $svg[] = '    <text x="32" y="12.5" font-size="10" font-family="ui-sans-serif, system-ui, sans-serif" font-weight="bold" fill="#f8fafc" text-anchor="middle">';
            $svg[] = "      <tspan fill=\"{$badgeColor}\">{$genderSymbol}</tspan> {$age}y";
            $svg[] = '    </text>';
            $svg[] = '  </g>';
        }

        $svg[] = '</svg>';

        return implode("\n", $svg);
    }

    /**
     * Generates a base64 data URI string for the avatar SVG.
     */
    public function generateDataUri(
        string $gender = 'male',
        int $age = 30,
        string $nationality = 'Japanese',
        ?string $role = null,
        ?string $status = null,
        int $pixelSize = 64,
        bool $includeBadges = false,
        ?string $seed = null
    ): string {
        $svg = $this->generateSvg($gender, $age, $nationality, $role, $status, $pixelSize, $includeBadges, $seed);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Helper to render avatar for an Npc or User model.
     */
    public function forPerson(Npc|User $person, int $pixelSize = 64, bool $includeBadges = false): string
    {
        $gender = $person->gender ?? 'male';
        $age = $person->age ?? 30;
        $nationality = $person->nationality ?? 'Japanese';
        $role = $person instanceof Npc ? ($person->role_archetype?->value ?? null) : ($person->colaborer_archetype ?? 'missionary');
        $status = $person instanceof Npc ? ($person->status?->value ?? null) : 'green';
        $seed = $person->name ?? (string) $person->id;

        return $this->generateSvg($gender, $age, $nationality, $role, $status, $pixelSize, $includeBadges, $seed);
    }
}
