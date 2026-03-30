{{--
    Resubmit flow: file inputs cannot be prefilled by the browser. Show current file + guidance.

    @var \App\Models\Application $application
    @var string $docColumn e.g. irinn_signature_proof_path
--}}
@php
    $path = isset($application) ? $application->getAttribute($docColumn) : null;
@endphp
@if(!empty($isNormalizedResubmission) && isset($application) && filled($path))
    <div class="small mb-2 p-2 rounded border bg-light irinn-resubmit-doc-hint">
        <div>
            <span class="text-success fw-semibold">We already have a file on record for this field.</span>
            <a href="{{ route('user.applications.document', ['id' => $application->id, 'doc' => $docColumn]) }}"
               target="_blank"
               rel="noopener noreferrer"
               class="ms-1">View current upload</a>
        </div>
        <p class="text-muted mb-0 mt-1 small">
            For security, browsers cannot pre-select files. Use &ldquo;Choose file&rdquo; below <strong>only if</strong> you want to replace the document you submitted earlier.
        </p>
    </div>
@endif
