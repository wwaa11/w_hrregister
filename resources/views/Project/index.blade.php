@extends("layout")
@section("content")
    <div class="m-auto w-full p-3 md:w-3/4">
        <div class="mb-6 rounded-lg border border-[#eaf7ab] bg-[#c1dccd] p-3 shadow">
            <div class="text-3xl text-[#1a3f34]">รายการลงทะเบียนของฉัน</div>
            <hr class="border-[#eaf7ab] shadow">
            <div class="text-sm text-red-600">ต้องการเปลี่ยนวันที่ลงทะเบียน กรุณายกเลิกวันลงทะเบียนเดิมก่อน</div>
            @foreach ($myItem as $transaction)
                @if ($transaction->item->slot->slot_date >= date("Y-m-d"))
                    <div class="mt-3 flex flex-row rounded border border-[#eaf7ab] bg-[#eeeeee] p-3 shadow">
                        <div class="m-auto w-[30%] text-center">
                            <div class="text-sm">{{ $transaction->item->slot->dateThai }}</div>
                            <div class="text-3xl text-[#008387]">{{ date("d", strtotime($transaction->item->slot->slot_date)) }}</div>
                            <div>{{ $transaction->item->slot->monthThai }}</div>
                        </div>
                        <div class="relative flex-1 border-l-2 border-[#6d6d6d] px-3">
                            <div class="prompt-medium text-2xl text-[#008387]">{{ $transaction->item->slot->project->project_name }}</div>
                            <div class="mt-2"><i class="fa-regular fa-clock text-[#008387]"></i> {{ $transaction->item->item_name }}</div>
                            @if ($transaction->item->item_note_1_active)
                                <div class="mt-2"><i class="fa-solid fa-map-pin text-[#008387]"></i></i> {{ $transaction->item->item_note_1_title }} : {{ $transaction->item->item_note_1_value }}</div>
                            @endif
                            @if (!$transaction->checkin)
                                <span class="absolute bottom-0 right-0 cursor-pointer text-red-600" onclick="deleteTransaction('{{ $transaction->item->slot->project->id }}','{{ $transaction->item->slot->project->project_name }}')"><i class="fa-solid fa-trash"></i></span>
                            @endif
                            @if (date("Y-m-d") == $transaction->item->slot->slot_date)
                                @if (!$transaction->checkin)
                                    <button class="mt-3 cursor-pointer rounded border border-[#eaf7ab] bg-red-500 p-3 text-white" onclick="sign('{{ $transaction->id }}','{{ $transaction->item->slot->project->project_name }}')">
                                        <i class="fa-solid fa-location-dot"></i> CHECK IN
                                    </button>
                                @else
                                    <div class="mt-3 text-green-700"><i class="fa-solid fa-location-dot"></i> CHECK IN {{ date("H:i", strtotime($transaction->checkin_datetime)) }}</div>
                                    @if ($transaction->hr_approve)
                                        <div class="mt-3 text-green-700">
                                            HR : อนุมัติ {{ date("H:i", strtotime($transaction->hr_approve_datetime)) }}
                                        </div>
                                    @else
                                        <div class="mt-3 text-red-600">
                                            HR : รอการอนุมัติ
                                        </div>
                                    @endif
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
        <div class="rounded-lg border border-[#eaf7ab] bg-[#c1dccd] p-3 shadow">
            <div class="text-3xl text-[#1a3f34]">รายการที่เปิดลงทะเบียน</div>
            <hr class="border-[#eaf7ab] shadow">
            @foreach ($projects as $index => $project)
                <a href="{{ env("APP_URL") }}/project/{{ $project->id }}">
                    <div class="m-3 cursor-pointer rounded border border-[#eaf7ab] bg-[#eeeeee] p-6">
                        <div class="text-2xl">{{ $index + 1 }}. {{ $project->project_name }}</div>
                        <div class="text-gray-500"><i class="fa-regular fa-calendar text-[#008387]"></i> {{ $project->project_detail }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
@endsection
@section("scripts")
    <script>
        async function deleteTransaction(id, name) {
            alert = await Swal.fire({
                title: "ยืนยันการเปลี่ยนวันที่ลงทะเบียน " + name,
                // html: "การเปลี่ยนรอบการลงทะเบียน ระบบจะทำการลบข้อมูลการลงทะเบียนออกจึงจะสามารถเปลี่ยนรอบการลงทะเบียนได้<br><span class=\"text-red-600\">*กรณีที่วันที่เลือกวันที่ต้องการลงทะเบียนไม่ได้ และ วันที่ลงทะเบียนขณะนี้เต็ม จะต้องทำการเปลี่ยนวันที่ใหม่ไม่สามารถนำวันที่ลงทะเบียนเดิมกลับมาได้</span>",
                icon: 'warning',
                allowOutsideClick: false,
                showConfirmButton: true,
                confirmButtonColor: 'red',
                confirmButtonText: 'ยืนยัน',
                showCancelButton: true,
                cancelButtonColor: 'gray',
                cancelButtonText: 'ยกเลิก',
            })

            if (alert.isConfirmed) {
                axios.post('{{ env("APP_URL") }}/delete', {
                    'project_id': id
                }).then((res) => {
                    Swal.fire({
                        title: res['data']['message'],
                        icon: 'success',
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: 'green'
                    }).then(function(isConfirmed) {
                        if (isConfirmed) {
                            window.location.reload()
                        }
                    })
                });
            }
        }
        async function sign(id, project_name) {
            alert = await Swal.fire({
                title: "ลงชื่อ : " + project_name,
                icon: 'warning',
                allowOutsideClick: false,
                showConfirmButton: true,
                confirmButtonColor: 'green',
                confirmButtonText: 'ลงชื่อ',
                showCancelButton: true,
                cancelButtonColor: 'gray',
                cancelButtonText: 'ยกเลิก',
            })

            if (alert.isConfirmed) {
                axios.post('{{ env("APP_URL") }}/sign', {
                    'transaction_id': id
                }).then((res) => {
                    Swal.fire({
                        title: res['data']['message'],
                        icon: 'success',
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: 'green'
                    }).then(function(isConfirmed) {
                        if (isConfirmed) {
                            window.location.reload()
                        }
                    })
                });
            }
        }
    </script>
@endsection
