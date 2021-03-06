<?php

namespace Tests\Feature;

use App\Task;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\Passport;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    /**
    * @test
    */
    public function logged_user_can_create_a_task()
    {
        Passport::actingAs($this->createUser(), ['create-tasks']);

        $task = [
            'name' => 'Testing task',
        ];
        $this->actingAs($this->createUser())->post(route('task.store', $task))
             ->assertStatus(201)
             ->assertJson([
                 'message' => 'Task created successfully!'
             ]);

        $this->assertDatabaseHas('tasks', $task);
    }

    /**
    * @test
    */
    public function logged_user_can_update_a_task()
    {
        $user = $this->createUser();
        Passport::actingAs($user, ['update-tasks']);

        $task = $this->createTask(['user_id' => $user->id]);

        $data = [
            'name' => 'Updated task',
        ];

        $this->put(route('task.update', $task), $data)
             ->assertOk()
             ->assertJson([
                 'message' => 'Task updated successfully!'
             ]);

        $data = [
            'completed' => true
        ];
        $this->put(route('task.update', $task), $data)
        ->assertOk()
        ->assertJson([
            'message' => 'Task updated successfully!'
        ]);

        $task->completed = true;
        $this->assertDatabaseMissing('tasks', $task->toArray());
        $this->assertDatabaseHas('tasks', $data);
    }

    /**
    * @test
    */
    public function logged_user_can_see_specific_task()
    {
        $user = $this->createUser();
        Passport::actingAs($user, ['view-tasks']);

        $this->assertAuthenticated();
        $task = $this->createTask(['user_id' => $user->id]);

        $this->get(route('task.show', $task))->assertOk()->assertJson([
            'task' => [
                'id' => $task->id,
                'name' => $task->name,
                'completed' => $task->completed ? 1 : 0,
                'created_at' => $task->created_at->diffForHumans()
            ]
        ]);
    }

    /**
    * @test
    */
    public function logged_user_can_see_a_list_of_task()
    {
        $user = $this->createUser();
        Passport::actingAs($user, ['view-tasks']);

        $this->createTask(['user_id' => $user->id], 2);

        $tasks = $user->tasks;
        $tasks = $tasks->map(function ($task) {
            return [
                'id' => $task->id,
                'name' => $task->name,
                'completed' => $task->completed ? 1 : 0,
                'created_at' => $task->created_at->diffForHumans()
            ];
        });

        $this->get(route('task.index'))->assertOk()->assertJson(['tasks' => $tasks->toArray()]);
    }

    /**
    * @test
    */
    public function logged_user_can_delete_task()
    {
        $user = $this->createUser();
        Passport::actingAs($user, ['delete-task']);

        $task = $this->createTask(['user_id' => $user->id]);

        $this->delete(route('task.delete', $task))
             ->assertStatus(204);

        $this->assertDatabaseMissing('tasks', $task->toArray());
    }

    private function createUser($args = [])
    {
        return factory(User::class)->create($args);
    }

    private function createTask($args = [], $n = 1)
    {
        if ($n == 1) {
            return factory(Task::class)->create($args);
        }

        return factory(Task::class, $n)->create($args);
    }

    public function setUp() : void
    {
        parent::setUp();

        Artisan::call('passport:install', ['-vvv' => true]);
    }
}
