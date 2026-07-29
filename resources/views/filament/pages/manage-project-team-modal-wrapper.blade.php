<div>
    @livewire(\App\Livewire\ManageProjectTeamModal::class, [
        'projectId' => $projectId ?? null,
        'isReadOnly' => $isReadOnly ?? false,
    ])
</div>
