<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Okipa\LaravelTable\Abstracts\AbstractTableConfiguration;
use Okipa\LaravelTable\Column;
use Okipa\LaravelTable\Formatters\Boolean;
use Okipa\LaravelTable\Formatters\Datetime;
use Okipa\LaravelTable\Formatters\StrLimit;
use Okipa\LaravelTable\Table;
use Tests\Models\Company;
use Tests\Models\User;
use Tests\TestCase;

class ColumnFormatTest extends TestCase
{
    /** @test */
    public function it_can_format_column_from_closure(): void
    {
        $users = User::factory()->count(2)->create();
        Company::factory()->count(6)->create();
        $config = new class extends AbstractTableConfiguration {
            protected function table(): Table
            {
                return Table::make()->model(User::class);
            }

            protected function columns(): array
            {
                return [
                    Column::make('Owned companies')
                        ->format(fn(User $user) => '<b> ' . $user->companies->implode('name', ', ') . '</b>'),
                ];
            }
        };
        Livewire::test(\Okipa\LaravelTable\Livewire\Table::class, ['config' => $config::class])
            ->call('init')
            ->assertSeeHtmlInOrder([
                '<tbody>',
                '<b> ' . $users->first()->companies->implode('name', ', ') . '</b>',
                '<b> ' . $users->last()->companies->implode('name', ', ') . '</b>',
                '</tbody>',
            ]);
    }

    /** @test */
    public function it_can_format_columns_from_formatters(): void
    {
        $user1 = User::factory()->create(['active' => true]);
        Date::setTestNow(Date::now()->addMinute());
        $user2 = User::factory()->create(['active' => false]);
        $config = new class extends AbstractTableConfiguration {
            protected function table(): Table
            {
                return Table::make()->model(User::class);
            }

            protected function columns(): array
            {
                return [
                    Column::make('Name')->format(new StrLimit(5)),
                    Column::make('Created At')->format(new Datetime(
                        'd/m:Y H:i:s',
                        'Europe/Paris'
                    )),
                    Column::make('Active')->format(new Boolean()),
                ];
            }
        };
        Livewire::test(\Okipa\LaravelTable\Livewire\Table::class, ['config' => $config::class])
            ->call('init')
            ->assertSeeHtmlInOrder([
                '<tbody>',
                Str::limit($user1->name, 5),
                $user1->created_at->timezone('Europe/Paris')->format('d/m:Y H:i:s'),
                '<i class="fa-solid fa-check text-success"></i>',
                Str::limit($user2->name, 5),
                $user2->created_at->timezone('Europe/Paris')->format('d/m:Y H:i:s'),
                '<i class="fa-solid fa-xmark text-danger"></i>',
                '</tbody>',
            ]);
    }

    /** @test */
    public function it_can_format_column_with_html_escaping(): void
    {
        $users = User::factory()->count(2)->create();
        Company::factory()->count(6)->create();
        $config = new class extends AbstractTableConfiguration {
            protected function table(): Table
            {
                return Table::make()->model(User::class);
            }

            protected function columns(): array
            {
                return [
                    Column::make('Owned companies')
                        ->format(fn(User $user) => '<b> ' . $user->companies->implode('name', ', ') . '</b>', true),
                ];
            }
        };
        Livewire::test(\Okipa\LaravelTable\Livewire\Table::class, ['config' => $config::class])
            ->call('init')
            ->assertSeeHtmlInOrder([
                '<tbody>',
                e('<b> ' . $users->first()->companies->implode('name', ', ') . '</b>'),
                e('<b> ' . $users->last()->companies->implode('name', ', ') . '</b>'),
                '</tbody>',
            ]);
    }
}
