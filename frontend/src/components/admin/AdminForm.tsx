"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import type { FormEvent } from "react";

type Field = {
    name: string;
    label: string;
    type?: "text" | "number" | "select" | "textarea";
    options?: string[];
};

type Props = {
    title: string;
    fields: Field[];
    backHref: string;
};

export default function AdminForm({ title, fields, backHref }: Props) {
    const router = useRouter();

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        alert("Đã lưu (giao diện demo, chưa kết nối API)");
        router.push(backHref);
    };

    return (
        <div className="p-6">
            <h1 className="mb-4 text-2xl font-semibold">{title}</h1>

            <form onSubmit={handleSubmit} className="max-w-xl space-y-4 rounded-lg border bg-white p-6">
                {fields.map((f) => (
                    <div key={f.name}>
                        <label className="mb-1 block text-sm font-medium">{f.label}</label>

                        {f.type === "select" ? (
                            <select name={f.name} className="w-full rounded-lg border px-3 py-2 text-sm">
                                {(f.options ?? []).map((o) => (
                                    <option key={o} value={o}>{o}</option>
                                ))}
                            </select>
                        ) : f.type === "textarea" ? (
                            <textarea name={f.name} rows={4} className="w-full rounded-lg border px-3 py-2 text-sm" />
                        ) : (
                            <input name={f.name} type={f.type || "text"} className="w-full rounded-lg border px-3 py-2 text-sm" />
                        )}
                    </div>
                ))}

                <div className="flex gap-3">
                    <button type="submit" className="rounded-lg bg-black px-4 py-2 text-sm text-white hover:bg-gray-800">
                        Lưu
                    </button>
                    <Link href={backHref} className="rounded-lg border px-4 py-2 text-sm">
                        Hủy
                    </Link>
                </div>
            </form>
        </div>
    );
}