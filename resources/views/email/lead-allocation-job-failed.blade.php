<p>There was an error while running your job:</p>
<p>{{ $exception->getMessage() }}</p>
<p>Code: {{ $exception->getCode() }}</p>
<p>File: {{ $exception->getFile() }}</p>
<p>Line: {{ $exception->getLine() }}</p>
<pre>{{ $exception->getTraceAsString() }}</pre>
