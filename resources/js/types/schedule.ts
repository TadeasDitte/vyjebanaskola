export type RegionOption = {
    value: string;
    label: string;
};

export type WeekParityValue = 'every' | 'odd' | 'even';

export type WeekParityOption = {
    value: WeekParityValue;
    label: string;
};

export type HolidayType = 'statny_sviatok' | 'prazdniny';

export type NearestHoliday = {
    name: string;
    type: HolidayType;
    start: string;
    end: string;
    single_day: boolean;
    days_until: number;
    ongoing: boolean;
};

export type PublicStats = {
    region_label: string;
    school_year_end: string;
    days_to_school_year_end: number;
    raw_school_days_left: number;
    nearest_holidays: NearestHoliday[];
};

export type Flair = {
    id: number;
    label: string;
    color: string;
    sentiment: number;
    position: number;
};

export type Teacher = {
    id: number;
    short_code: string;
    full_name: string | null;
    note: string | null;
    flair_id: number | null;
    lessons_count?: number;
};

export type Lesson = {
    id: number;
    teacher_id: number | null;
    day_of_week: number;
    period: number;
    subject_code: string;
    subject_name: string | null;
    room: string | null;
    group_label: string | null;
    week_parity: WeekParityValue;
};

export type TeacherProjection = {
    teacher_id: number | null;
    short_code: string;
    full_name: string | null;
    flair: { id: number; label: string; color: string; sentiment: number } | null;
    remaining: number;
    next_date: string | null;
};

export type FlairProjection = {
    flair_id: number | null;
    label: string;
    color: string;
    sentiment: number;
    remaining: number;
};

export type Projection = {
    has_region: boolean;
    range_start: string | null;
    range_end: string | null;
    school_days_remaining: number;
    total_remaining: number;
    next_school_day: string | null;
    per_teacher: TeacherProjection[];
    per_flair: FlairProjection[];
};
