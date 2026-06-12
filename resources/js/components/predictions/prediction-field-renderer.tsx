import { Input } from '@/components/ui/input';
import type { PredictionFieldType, PredictionPickerOption } from '@/types';

type Props = {
    id: string;
    fieldType: PredictionFieldType;
    value: unknown;
    disabled?: boolean;
    validationSchema?: Record<string, unknown> | null;
    options?: PredictionPickerOption[];
    onChange: (value: unknown) => void;
};

export default function PredictionFieldRenderer({
    id,
    fieldType,
    value,
    disabled = false,
    validationSchema,
    options = [],
    onChange,
}: Props) {
    if (fieldType === 'team_picker' || fieldType === 'player_picker') {
        return (
            <select
                id={id}
                className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                value={value === null || value === undefined ? '' : String(value)}
                onChange={(event) => {
                    if (event.target.value === '') {
                        onChange(null);

                        return;
                    }

                    const selectedOption = options.find(
                        (option) => String(option.value) === event.target.value,
                    );

                    onChange(selectedOption ? selectedOption.value : event.target.value);
                }}
                disabled={disabled}
            >
                <option value="">Select an option</option>
                {options.map((option) => (
                    <option key={String(option.value)} value={String(option.value)}>
                        {option.label}
                    </option>
                ))}
            </select>
        );
    }

    if (fieldType === 'boolean') {
        return (
            <select
                id={id}
                className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs md:text-sm"
                value={typeof value === 'boolean' ? String(value) : ''}
                onChange={(event) => {
                    if (event.target.value === '') {
                        onChange(null);

                        return;
                    }

                    onChange(event.target.value === 'true');
                }}
                disabled={disabled}
            >
                <option value="">Select</option>
                <option value="true">True</option>
                <option value="false">False</option>
            </select>
        );
    }

    if (fieldType === 'number') {
        const min = typeof validationSchema?.min === 'number' ? validationSchema.min : undefined;
        const max = typeof validationSchema?.max === 'number' ? validationSchema.max : undefined;

        return (
            <Input
                id={id}
                type="number"
                min={min}
                max={max}
                value={value === null || value === undefined ? '' : String(value)}
                onChange={(event) => {
                    onChange(event.target.value === '' ? null : Number(event.target.value));
                }}
                disabled={disabled}
            />
        );
    }

    if (fieldType === 'date' || fieldType === 'time') {
        return (
            <Input
                id={id}
                type={fieldType}
                value={typeof value === 'string' ? value : ''}
                onChange={(event) => onChange(event.target.value === '' ? null : event.target.value)}
                disabled={disabled}
            />
        );
    }

    return (
        <Input
            id={id}
            type="text"
            value={typeof value === 'string' ? value : ''}
            onChange={(event) => onChange(event.target.value)}
            disabled={disabled}
        />
    );
}
