@extends('device.submenu')

@section('tabcontent')
    <x-panel title="{{ __('Device Notes') }}">
        <form method="post">
            @csrf
            <div class="form-group">
                <textarea class="form-control" rows="3" id="device-notes">{{ $device->notes }}</textarea>
            </div>
            <button type="submit" class="btn btn-default" id="btn-update-notes" data-device_id="{{ $device->device_id }}"><i class="fa fa-check"></i> Save</button>
        </form>
    </x-panel>
@endsection

@push('scripts')
    <script type="text/javascript">
        $('#btn-update-notes').on('click', function(event) {
            event.preventDefault();
            var $this = $(this);
            var device_id = $(this).data("device_id");
            var notes = $("#device-notes").val();
            $.ajax({
                type: 'POST',
                url: 'ajax_form.php',
                data: { type: "update-notes", notes: notes, device_id: device_id},
                dataType: "json",
                success: function(data){
                    if (data.status == "error") {
                        toastr.error(data.message);
                    } else {
                        toastr.success('Saved');
                    }
                },
                error:function(){
                    toastr.error('Error');
                }
            });
        });
    </script>
@endpush
