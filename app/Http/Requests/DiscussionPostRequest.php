<?php

namespace App\Http\Requests;

use App\Models\DiscussionTopic;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DiscussionPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $topic = $this->route('discussion_topic');

        return $topic instanceof DiscussionTopic && $this->user()?->can('reply', $topic) === true;
    }

    public function rules(): array
    {
        return ['body' => ['required', 'string', 'min:2', 'max:10000'], 'parent_id' => ['nullable', 'exists:discussion_posts,id']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->filled('parent_id')) {
                $parent = $this->route('discussion_topic')?->posts()->find($this->integer('parent_id'));
                if (! $parent || $parent->parent_id) {
                    $validator->errors()->add('parent_id', 'Balasan hanya boleh satu tingkat pada posting topik yang sama.');
                }
            }
        }];
    }
}
