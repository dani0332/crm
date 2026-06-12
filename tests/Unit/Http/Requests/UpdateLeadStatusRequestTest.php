<?php

use App\Http\Requests\UpdateLeadStatusRequest;
use Illuminate\Support\Facades\Validator;

beforeEach(function (): void {
    $this->request = UpdateLeadStatusRequest::create('/', 'POST', [
        'modelType' => 'Car',
        'quote_uuid' => 'test-uuid',
        'leadStatus' => 1,
        'notes' => null,
    ]);
    $this->request->setContainer(app());
});

it('sanitizeNotesFromEmoji helper removes emoji and returns text only', function (): void {
    expect(sanitizeNotesFromEmoji('Follow up call done 😀'))->toBe('Follow up call done')
        ->and(sanitizeNotesFromEmoji('Test 🎉 and 👍'))->toBe('Test and')
        ->and(sanitizeNotesFromEmoji('👍'))->toBe('');
});

it('sanitizeNotesFromEmoji helper returns empty string for null or empty input', function (): void {
    expect(sanitizeNotesFromEmoji(null))->toBe('')
        ->and(sanitizeNotesFromEmoji(''))->toBe('');
});

it('sanitizeNotesFromEmoji helper leaves plain text unchanged', function (): void {
    $text = 'Customer requested callback next week.';
    expect(sanitizeNotesFromEmoji($text))->toBe($text);
});

it('sanitizeNotesFromEmoji helper preserves ASCII digits hash and asterisk in business notes', function (): void {
    $note = 'Quote #1234 - 5 units *priority*';
    expect(sanitizeNotesFromEmoji($note))->toBe($note);
});

it('sanitizeNotesFromEmoji helper preserves copyright registered and trademark symbols in notes', function (): void {
    $note = "Company\u{00AE} policy \u{00A9} 2025 Acme\u{2122}";
    expect(sanitizeNotesFromEmoji($note))->toBe($note);
});

it('sanitizeNotesFromEmoji helper still removes digit keycap emoji grapheme clusters', function (): void {
    $keycapOne = "Pick \u{0031}\u{FE0F}\u{20E3} staff";
    expect(sanitizeNotesFromEmoji($keycapOne))->toBe('Pick staff');
});

it('sanitizeNotesFromEmoji helper preserves newlines and paragraph breaks', function (): void {
    $multiline = "Line 1\nLine 2\r\n\nParagraph 2";
    expect(sanitizeNotesFromEmoji($multiline))->toBe($multiline);
});

it('sanitizeNotesFromEmoji helper removes emoji but keeps line breaks in multi-line notes', function (): void {
    expect(sanitizeNotesFromEmoji("First 😀\nSecond line"))->toBe("First \nSecond line");
});

it('sanitizeNotesFromEmoji helper removes ZWJ compound emoji without leaving invisible joiners or selectors', function (): void {
    $family = 'Notes 👨‍👩‍👧 end';
    $technologist = 'Role 👩‍💻 here';
    $flag = 'Pride 🏳️‍🌈 event';

    expect(sanitizeNotesFromEmoji($family))->toBe('Notes end')
        ->and(sanitizeNotesFromEmoji($technologist))->toBe('Role here')
        ->and(sanitizeNotesFromEmoji($flag))->toBe('Pride event')
        ->and(sanitizeNotesFromEmoji($family))->not->toContain("\u{200D}")
        ->and(sanitizeNotesFromEmoji($flag))->not->toContain("\u{FE0F}");
});

it('prepareForValidation sanitizes notes so saved value has no emoji', function (): void {
    $this->request->merge(['notes' => 'Follow up call done 😀']);

    $ref = new ReflectionMethod(UpdateLeadStatusRequest::class, 'prepareForValidation');
    $ref->setAccessible(true);
    $ref->invoke($this->request);

    expect($this->request->input('notes'))->toBe('Follow up call done');
});

it('prepareForValidation sanitizes lost_notes', function (): void {
    $this->request->merge(['lost_notes' => 'Lost reason notes 🎉']);

    $ref = new ReflectionMethod(UpdateLeadStatusRequest::class, 'prepareForValidation');
    $ref->setAccessible(true);
    $ref->invoke($this->request);

    expect($this->request->input('lost_notes'))->toBe('Lost reason notes');
});

it('passes validation when notes contain emoji because they are sanitized before validation', function (): void {
    $this->request->merge(['notes' => 'Customer said yes 😀']);

    $ref = new ReflectionMethod(UpdateLeadStatusRequest::class, 'prepareForValidation');
    $ref->setAccessible(true);
    $ref->invoke($this->request);

    $validator = Validator::make($this->request->all(), $this->request->rules());

    expect($validator->errors()->has('notes'))->toBeFalse()
        ->and($this->request->input('notes'))->toBe('Customer said yes');
});
