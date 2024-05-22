<x-template title="Admin Dashboard" nav="home">



    <main class="columns">



        <section class="half-60">


            <div class="card">
                <div clas="card-header">
                    <div class="card-title">
                        Overview
                    </div>
                </div>


                <div class="card-body">

                    <section class="grid sm:grid-cols-2 grid-rows-1 gap-4">


                        <div class="panel rounded-lg">
                            <div class="panel-header">
                                <div class="md:flexx md:flex-col md:items-center md:gap-2">
                                    <img src="{{ asset($user->picture()) }}" alt="advisor"
                                        class="aspect-square w-12 rounded-full object-cover">
                                    <div>
                                        <p class="font-semibold text-xl">{{ $user->name }}</p>
                                        <p class="font-medium text-red-500 text-pretty">ADMINISTRATOR</p>
                                    </div>
                                </div>
                                <div class="panel-icons"><!----></div>
                            </div>
                        </div>


                        <div
                            class="bg-[--blue-200] p-4 flex flex-col justify-between overflow-hidden rounded-md min-h-32">
                            <header class="w-full flex items-center justify-between">
                                <p class="font-medium uppercase">students</p><span
                                    class="material-symbols-rounded">groups</span>
                            </header>
                            <div>
                                <h1 class="font-medium text-4xl" ng-bind="config.count.students||0"></h1>
                                <h1 class="font-medium text-4xl"></h1>
                            </div>
                        </div>



                        <div
                            class="bg-[--red-200] p-4 flex flex-col justify-between overflow-hidden rounded-md min-h-32">
                            <header class="w-full flex items-center justify-between">
                                <p class="font-medium uppercase">staff</p><span
                                    class="material-symbols-rounded">work</span>
                            </header>
                            <div>
                                <h1 class="font-medium text-4xl" ng-bind="config.count.staffs||0"></h1>
                                <h1 class="font-medium text-4xl"></h1>
                            </div>
                        </div>
                        <div
                            class="bg-[--purple-200] p-4 flex flex-col justify-between overflow-hidden rounded-md min-h-32">
                            <header class="w-full flex items-center justify-between">
                                <p class="font-medium uppercase">advisors</p><span
                                    class="material-symbols-rounded">group</span>
                            </header>
                            <div>
                                <h1 class="font-medium text-4xl" ng-bind="config.count.advisors||0"></h1>
                                <h1 class="font-medium text-4xl"></h1>
                            </div>
                        </div>

                        <div
                            class="bg-[--yellow-200] p-4 flex flex-col justify-between overflow-hidden rounded-md min-h-32">
                            <header class="w-full flex items-center justify-between">
                                <p class="font-medium uppercase">courses</p><span
                                    class="material-symbols-rounded">book_2</span>
                            </header>
                            <div>
                                <h1 class="font-medium text-4xl" ng-bind="config.count.courses||0"></h1>
                                <h1 class="font-medium text-4xl"></h1>
                            </div>
                        </div>
                        <div
                            class="bg-[--primary-200] p-4 flex flex-col justify-between overflow-hidden rounded-md min-h-32">
                            <header class="w-full flex items-center justify-between">
                                <p class="font-medium uppercase" ng-bind="config.active_session.name"></p><span
                                    class="material-symbols-rounded">book_2</span>
                            </header>
                            <div>
                                <h1 class="font-medium text-4xl" ng-bind="config.active_session.active_semester"></h1>
                                <h1 class="font-medium text-4xl"></h1>
                            </div>
                        </div>
                    </section>



                    <div class="card2 ">
                        <div class="card2-header">
                            <h2>Student Survey</h2>
                        </div>
                        <div class="card2-body min-h-[365px]">
                            <canvas data-label="Student Survey" class="flex-1 object-fit" id="barChart" width="400"
                                height="400"></canvas>
                        </div>
                    </div>
                </div>

            </div>

        </section>

        <section>
            <div class="cardx">

                <div class="card-body flex flex-col gap-4">
                    <x-calendar />




                    @php
                        $activityLogs = \App\Models\ActivityLog::orderBy('created_at', 'desc')->paginate(7);
                        $countLog = count($activityLogs);

                    @endphp
                    <div class="card2">
                        <div class="card2-header">Log Activities</div>
                        <div class="card2-body italic flex flex-col gap-3 min-h-[291px]">

                            @forelse ($activityLogs as $log)
                                <div><b>{{ $log->user->id === auth()->id() ? 'You' : $log->user->name }}</b>
                                    {{ $log->description }} <i class="fa fa-clock opacity-45"></i> <span
                                        class="text-slate-400">{{ timeago($log->created_at) }}</span></div>
                            @empty
                                <p>No activity logs found.</p>
                            @endforelse
                        </div>
                        @if ($countLog > 0)
                            <div class="card2-footer">{{ $activityLogs->links() }}</div>
                        @endif
                    </div>

                    <x-todo />

                </div>



            </div>
        </section>

    </main>











    <script src="{{ asset('js/chart.js') }}"></script>

    <script src="{{ asset('js/jchart.js') }}"></script>
    <script>
        chart('#barChart', {
            A: 4,
            B: 3,
            C: 10,
            D: 20,
            E: 5,
            F: 2
        }, 'bar')
        chart('#pieChart', {
            A: 4,
            B: 3,
            C: 10,
            D: 20,
            E: 5,
            F: 2
        }, 'pie')


        //chart.pieChart('#gradeChart');
    </script>
</x-template>