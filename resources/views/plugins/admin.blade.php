@extends('layouts.librenmsv1')

@section('title', trans('plugins.admin_page'))

@section('content')
    <div class="container">
        <div class="panel panel-default panel-condensed col-md-6 col-md-offset-3 col-xs-12 col-sm-8 col-sm-offset-2" style="padding: 0">
            <div class="panel-heading">
                <strong>{{ __('plugins.admin_title') }}</strong>
            </div>

            <table class="table table-condensed">
                <tr>
                    <th>Name</th>
                    <th>Action</th>
                </tr>
                @foreach($plugins as $plugin)
                    <tr class="{{ $plugin->plugin_active ? 'bg-success' : 'bg-danger' }}">
                        <td>{{ $plugin->plugin_name }}</td>
                        <td>
                            <form class="form-inline" role="form" action="{{ route('plugin.update', ['plugin' => $plugin->plugin_name]) }}" method="post" id="{{ $plugin->plugin_id }}" name="{{ $plugin->plugin_id }}">
                                @csrf
                                @if($plugin->plugin_active)
                                    <input type="hidden" name="plugin_active" value="0">
                                    <button type="submit" class="btn btn-sm btn-danger" style="min-width: 66px">{{ __('Disable') }}</button>
                                @else
                                    <input type="hidden" name="plugin_active" value="1">
                                    <button type="submit" class="btn btn-sm btn-success" style="min-width: 66px">{{ __('Enable') }}</button>
                                @endif
                                @if($plugin->version == 1)
                                    <a href="{{ route('plugin.legacy', $plugin->plugin_name) }}" class="btn btn-sm btn-primary" style="min-width: 72px">{{ __('Page') }}</a>
                                @else
                                    <a href="{{ route('plugin.settings', $plugin->plugin_name) }}" class="btn btn-sm btn-primary" style="min-width: 72px">{{ __('Settings') }}</a>
                                @endif
                            </form>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>

        <div class="panel panel-default panel-condensed col-md-6 col-md-offset-3 col-xs-12 col-sm-8 col-sm-offset-2" style="padding: 0">
            <div class="panel-heading">
                <strong>{{ __('plugins.packages_title') }}</strong>
            </div>

            @if(empty($required))
                <div class="panel-body">
                    {{ __('plugins.packages_none') }}
                </div>
            @else
                <table class="table table-condensed">
                    <tr>
                        <th>{{ __('plugins.packages_package') }}</th>
                        <th>{{ __('plugins.packages_required') }}</th>
                        <th>{{ __('plugins.packages_installed') }}</th>
                    </tr>
                    @foreach($required as $name => $constraint)
                        <tr>
                            <td>{{ $name }}</td>
                            <td>{{ $constraint }}</td>
                            <td>{{ $installed[$name] ?? __('plugins.packages_missing') }}</td>
                        </tr>
                    @endforeach
                </table>
            @endif

            <div class="panel-footer">
                {{ __('plugins.packages_cli_only') }}
                <br><code>lnms plugin:add vendor/package</code>
            </div>
        </div>
    </div>
@endsection
