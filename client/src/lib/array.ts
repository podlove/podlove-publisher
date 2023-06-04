export const arrayMove = <T>(arr: T[], fromIndex: number, toIndex: number) => {
  const newArr = [...arr];
  newArr.splice(toIndex, 0, newArr.splice(fromIndex, 1)[0]);
  return newArr;
};
