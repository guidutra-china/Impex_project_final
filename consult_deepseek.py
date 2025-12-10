#!/usr/bin/env python3
import os
import json
from openai import OpenAI

# Use DeepSeek API
client = OpenAI(
    api_key=os.environ.get("DEEP_SEEK_2"),
    base_url="https://api.deepseek.com"
)

problem_description = """
I'm having an issue with Filament v3 FileUpload component in Laravel 11.

**Problem:**
- FileUpload shows "Upload complete" when user selects a file
- The file appears in the form temporarily
- When user clicks Save, the file disappears
- The file path is saved in database, but the physical file doesn't exist in storage/app/public/company/
- Error in logs: "Disk [spaces] does not have a configured driver"

**Current Setup:**
- Filament v3.6.4
- Livewire v3.6.4
- Laravel 11
- config/livewire.php has 'disk' => 'local'
- FileUpload configuration:
```php
FileUpload::make('logo_path')
    ->disk('public')
    ->directory('company')
    ->visibility('public')
    ->saveUploadedFileUsing(function ($file) {
        $filename = uniqid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('company', $filename, 'public');
        \\Log::info('File saved immediately to: ' . $path);
        return $path;
    })
```

**What I've tried:**
1. Created storage/app/livewire-tmp directory
2. Created storage/app/public/company directory
3. Ran php artisan storage:link
4. Set config/livewire.php with 'disk' => 'local'
5. Cleared all caches (config:clear, cache:clear, view:clear, optimize:clear)
6. Added saveUploadedFileUsing callback (but it's not being called - no logs)

**Question:**
Why is the file not being saved permanently? Why is saveUploadedFileUsing not being called? How to fix this?
"""

response = client.chat.completions.create(
    model="deepseek-chat",
    messages=[
        {"role": "system", "content": "You are an expert in Laravel, Filament, and Livewire. Provide detailed, practical solutions."},
        {"role": "user", "content": problem_description}
    ],
    temperature=0.7,
    max_tokens=2000
)

print("=" * 80)
print("DEEPSEEK RESPONSE:")
print("=" * 80)
print(response.choices[0].message.content)
print("=" * 80)
