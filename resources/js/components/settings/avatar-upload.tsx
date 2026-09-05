import { router } from '@inertiajs/react';
import { Camera } from 'lucide-react';
import { type ChangeEvent, useCallback, useRef, useState } from 'react';
import Cropper, { type Area } from 'react-easy-crop';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useInitials } from '@/hooks/use-initials';
import { getCroppedImageBlob } from '@/lib/crop-image';
import type { User } from '@/types';

/**
 * Kept out of the main profile Form (like EmailChangeForm) since it submits
 * the moment a crop is confirmed rather than waiting for the page's Save
 * button — the crop dialog already reads as its own confirmation step.
 */
export function AvatarUpload({ user }: { user: User }) {
    const getInitials = useInitials();
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [imageSrc, setImageSrc] = useState<string | null>(null);
    const [crop, setCrop] = useState({ x: 0, y: 0 });
    const [zoom, setZoom] = useState(1);
    const [rotation, setRotation] = useState(0);
    const [croppedAreaPixels, setCroppedAreaPixels] = useState<Area | null>(
        null,
    );
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const onCropComplete = useCallback((_area: Area, areaPixels: Area) => {
        setCroppedAreaPixels(areaPixels);
    }, []);

    function resetCropState() {
        if (imageSrc) {
            URL.revokeObjectURL(imageSrc);
        }
        setImageSrc(null);
        setCrop({ x: 0, y: 0 });
        setZoom(1);
        setRotation(0);
        setCroppedAreaPixels(null);
        setError(null);
    }

    function handleFileChange(event: ChangeEvent<HTMLInputElement>) {
        const file = event.target.files?.[0];
        event.target.value = '';

        if (!file) {
            return;
        }

        setImageSrc(URL.createObjectURL(file));
    }

    async function handleSave() {
        if (!imageSrc || !croppedAreaPixels) {
            return;
        }

        setProcessing(true);
        setError(null);

        let file: File;

        try {
            const blob = await getCroppedImageBlob(
                imageSrc,
                croppedAreaPixels,
                rotation,
            );
            file = new File([blob], 'avatar.jpg', { type: 'image/jpeg' });
        } catch {
            setError('That image could not be processed. Try another one.');
            setProcessing(false);
            return;
        }

        router.patch(
            ProfileController.update.url(),
            {
                name: user.name,
                phone: user.phone ?? '',
                gender: user.gender ?? '',
                avatar: file,
            },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => resetCropState(),
                onError: (errors) =>
                    setError(
                        (errors as { avatar?: string }).avatar ??
                            'Something went wrong. Please try again.',
                    ),
                onFinish: () => setProcessing(false),
            },
        );
    }

    return (
        <div className="flex items-center gap-4">
            <div className="group relative shrink-0">
                <Avatar className="size-16">
                    <AvatarImage
                        src={user.avatar_url ?? undefined}
                        alt={user.name}
                    />
                    <AvatarFallback className="text-lg">
                        {getInitials(user.name)}
                    </AvatarFallback>
                </Avatar>
                <button
                    type="button"
                    onClick={() => fileInputRef.current?.click()}
                    className="bg-background hover:bg-accent absolute -right-1 -bottom-1 flex size-6 items-center justify-center rounded-full border shadow-sm transition-colors"
                    aria-label="Change profile picture"
                    data-test="change-avatar-button"
                >
                    <Camera className="size-3.5" />
                </button>
            </div>

            <div className="grid gap-0.5">
                <span className="text-sm font-medium">Profile picture</span>
                <span className="text-muted-foreground text-sm">
                    JPG, PNG, or GIF. You can crop and rotate it before saving.
                </span>
            </div>

            <input
                ref={fileInputRef}
                type="file"
                accept="image/*"
                className="sr-only"
                onChange={handleFileChange}
            />

            <Dialog
                open={imageSrc !== null}
                onOpenChange={(open) => !open && resetCropState()}
            >
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Edit profile picture</DialogTitle>
                        <DialogDescription>
                            Drag to reposition, then adjust zoom and rotation.
                        </DialogDescription>
                    </DialogHeader>

                    {imageSrc && (
                        <div className="bg-muted relative h-72 w-full overflow-hidden rounded-md">
                            <Cropper
                                image={imageSrc}
                                crop={crop}
                                zoom={zoom}
                                rotation={rotation}
                                aspect={1}
                                cropShape="round"
                                showGrid={false}
                                onCropChange={setCrop}
                                onZoomChange={setZoom}
                                onRotationChange={setRotation}
                                onCropComplete={onCropComplete}
                            />
                        </div>
                    )}

                    <div className="grid gap-3">
                        <div className="grid gap-1.5">
                            <Label htmlFor="avatar-zoom">Zoom</Label>
                            <input
                                id="avatar-zoom"
                                type="range"
                                min={1}
                                max={3}
                                step={0.05}
                                value={zoom}
                                onChange={(event) =>
                                    setZoom(Number(event.target.value))
                                }
                                className="accent-foreground w-full"
                            />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="avatar-rotation">Rotate</Label>
                            <input
                                id="avatar-rotation"
                                type="range"
                                min={0}
                                max={360}
                                step={1}
                                value={rotation}
                                onChange={(event) =>
                                    setRotation(Number(event.target.value))
                                }
                                className="accent-foreground w-full"
                            />
                        </div>
                    </div>

                    {error && (
                        <p className="text-destructive text-sm">{error}</p>
                    )}

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="ghost"
                            onClick={resetCropState}
                            disabled={processing}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            onClick={handleSave}
                            disabled={processing || !croppedAreaPixels}
                            data-test="save-avatar-button"
                        >
                            {processing && <Spinner />}
                            Save picture
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>
    );
}
