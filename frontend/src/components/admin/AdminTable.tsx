import Link from "next/link";
import type { ReactNode } from "react";

type Column<T> = {
    key: string;
    label: string;
    render?: (value: unknown, row: T) => ReactNode;
};

type Props<T extends { id: number }> = {
    title: string;
    addHref: string;
    editBase: string;
    columns: Column<T>[];
    rows: T[];
};

export default function AdminTable<T extends { id: number }>({
    title,
    addHref,
    editBase,
    columns,
    rows,
}: Props<T>) {
    return (
        <div className="p-6">
            <div className="mb-4 flex items-center justify-between">
                <h1 className="text-2xl font-semibold">{title}</h1>
                <Link
                    href={addHref}
                    className="rounded-lg bg-black px-4 py-2 text-sm text-white hover:bg-gray-800"
                >
                    + Thêm mới
                </Link>
            </div>

            <div className="overflow-x-auto rounded-lg border bg-white">
                <table className="w-full text-left text-sm">
                    <thead className="bg-gray-100 text-gray-600">
                        <tr>
                            {columns.map((c) => (
                                <th key={c.key} className="px-4 py-3">{c.label}</th>
                            ))}
                            <th className="px-4 py-3">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.map((row) => (
                            <tr key={row.id} className="border-t">
                                {columns.map((c) => {
                                    const value = (row as Record<string, unknown>)[c.key];
                                    return (
                                        <td key={c.key} className="px-4 py-3">
                                            {c.render ? c.render(value, row) : String(value ?? "")}
                                        </td>
                                    );
                                })}
                                <td className="px-4 py-3">
                                    <Link href={`${editBase}/new`} className="mr-3 text-blue-600 hover:underline">
                                        Sửa
                                    </Link>
                                    <button className="text-red-600 hover:underline">Xóa</button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}